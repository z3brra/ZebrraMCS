<?php

namespace App\Command;

use App\Platform\Entity\MailAliasLink;
use App\Platform\Repository\MailAliasLinkRepository;
use App\Service\MailAlias\MailAliasGatewayService;

use Doctrine\ORM\EntityManagerInterface;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:backfill-mail-alias-links',
    description: 'Create missing mail alias uuid mappings for existing mailserver.aliases'
)]
final class BackfillMailAliasesCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly MailAliasGatewayService $mailAliasGateway,
        private readonly MailAliasLinkRepository $mailAliasLinkRepository,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('<info>Starting mail alias backfill...</info>');

        $rows = $this->mailAliasGateway->fetchAll();

        if ($rows === []) {
            $output->writeln('<comment>No aliases found in mailserver.</comment>');
            return Command::SUCCESS;
        }

        $existingMap = $this->mailAliasLinkRepository->mapUuidsByMailAliasIds(
            array_map(fn($r) => (int) $r['id'], $rows)
        );

        $created = 0;

        foreach ($rows as $row) {
            $mailAliasId = (int) $row['id'];

            if (isset($existingMap[$mailAliasId])) {
                continue;
            }

            $link = new MailAliasLink(
                mailAliasId: $mailAliasId,
                sourceEmail: mb_strtolower(trim((string) $row['source'])),
                destinationEmail: mb_strtolower(trim((string) $row['destination']))
            );

            $this->entityManager->persist($link);
            $created++;
        }

        if ($created > 0) {
            $this->entityManager->flush();
        }

        $output->writeln("<info>Backfill completed. Created {$created} missing alias links.</info>");

        return Command::SUCCESS;
    }
}

?>