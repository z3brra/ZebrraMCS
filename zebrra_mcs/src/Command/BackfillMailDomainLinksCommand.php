<?php

namespace App\Command;

use App\Platform\Entity\MailDomainLink;
use App\Platform\Repository\MailDomainLinkRepository;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:backfill-mail-domain-links',
    description: 'Create missing domain uuid mappings for existing mailserver.domains'
)]
final class BackfillMailDomainLinksCommand extends Command
{
    public function __construct(
        private readonly Connection $mailConnection,
        private readonly MailDomainLinkRepository $mailDomainRepository,
        private readonly EntityManagerInterface $entityManager
    )
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $rows = $this->mailConnection->fetchAllAssociative('SELECT id, name FROM domains ORDER BY id ASC');

        $created = 0;
        $skipped = 0;

        foreach ($rows as $row) {
            $mailDomainId = (int) $row['id'];

            $existing = $this->mailDomainRepository->findOneByMailDomainId($mailDomainId);
            if ($existing) {
                $skipped++;
                continue;
            }

            $link = new MailDomainLink($mailDomainId);
            $this->entityManager->persist($link);
            $created++;

            if ($created % 100 === 0) {
                $this->entityManager->flush();
                $this->entityManager->clear();
            }
        }

        $this->entityManager->flush();

        $output->writeln(sprintf('Done. created=%d skipped=%d total=%d', $created, $skipped, count($rows)));

        return Command::SUCCESS;
    }
}

?>