<?php

namespace App\Command;

use App\Platform\Entity\MailUserLink;
use App\Platform\Repository\MailUserLinkRepository;
use App\Service\MailUser\MailUserGatewayService;
use App\Service\Domain\MailDomainLinkResolver;

use Doctrine\ORM\EntityManagerInterface;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\{InputInterface, InputOption};
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

// test d'une commande symfony style (maybe à supprimer je suis pas fan du rendu trop chargé)
#[AsCommand(
    name: 'app:backfill-mail-user-links',
    description: 'Create missing users uuid mappings for existing mailserver.users'
)]
final class BackfillMailUserLinksCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly MailUserGatewayService $mailUserGateway,
        private readonly MailUserLinkRepository $mailUserLinkRepository,
        private readonly MailDomainLinkResolver $domainResolver,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Do not persits anything')
            ->addOption('limit', null, InputOption::VALUE_OPTIONAL, 'Limit number of users', null);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $dryRun = (bool) $input->getOption('dry-run');
        $limit = $input->getOption('limit');

        $io->title('Mail users synchronization');

        $users = $this->mailUserGateway->listAll($limit);

        $created = 0;
        $skipped = 0;

        foreach ($users as $user) {
            $mailUserId = (int) $user['id'];

            if ($this->mailUserLinkRepository->findOneByMailUserId($mailUserId) instanceof MailUserLink) {
                $skipped++;
                continue;
            }

            $mailDomainId = (int) $user['domain_id'];

            try {
                $this->domainResolver->resolveMailDomainUuid($mailDomainId);
            } catch (\Throwable) {
                $io->warning(sprintf(
                    'Skipping user %s : domain mapping missing',
                    $user['email']
                ));
                continue;
            }

            $link = new MailUserLink(
                mailUserId: $mailUserId,
                mailDomainId: $mailDomainId,
                email: $user['email']
            );

            if (!$dryRun) {
                $this->entityManager->persist($link);
            }

            $created++;
        }

        if (!$dryRun) {
            $this->entityManager->flush();
        }

        $io->success([
            'Sync completed',
            'Created links: ' . $created,
            'Skipped (already linked) : ' . $skipped,
            'Mode : ' . ($dryRun ? 'DRY-RUN' : 'WRITE'),
        ]);

        return Command::SUCCESS;
    }
}
?>