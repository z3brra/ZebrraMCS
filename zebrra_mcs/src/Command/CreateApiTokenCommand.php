<?php

namespace App\Command;

use App\Platform\Entity\{
    ApiToken,
    ApiTokenPermission,
    ApiTokenScope
};
use App\Platform\Repository\AdminUserRepository;
use App\Security\ApiTokenHasher;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;

use Ramsey\Uuid\Uuid;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;


#[AsCommand(
    name: 'app:create-api-token',
    description: 'Create an API token (dev utility)'
)]
final class CreateApiTokenCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly AdminUserRepository $adminUserRepository,
        private readonly ApiTokenHasher $tokenHasher,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var QuestionHelper $helper */
        $helper = $this->getHelper('question');

        $nameQuestion = new Question('Token name (e.g "Dev Project"): ');
        $nameQuestion->setValidator(fn ($value) => $value ? $value : throw new \RuntimeException('Name is required.'));
        $name = $helper->ask($input, $output, $nameQuestion);

        $admins = $this->adminUserRepository->findAll();
        if (!$admins) {
            $output->writeln('<error>No admin users found.</error>');
            return Command::FAILURE;
        }

        $adminChoices = array_map(fn ($a) => $a->getEmail(), $admins);
        $adminQuestion = new ChoiceQuestion('Created by admin : ', $adminChoices);
        $adminEmail = $helper->ask($input, $output, $adminQuestion);
        $admin = $this->adminUserRepository->findOneBy(['email' => $adminEmail]);

        $permQuestion = new Question(
            'Permissions (comma separated, e.g domains.read,mail.send): ',
            ''
        );
        $permInput = $helper->ask($input, $output, $permQuestion);
        $permissions = array_filter(array_map('trim', explode(',', $permInput)));

        $scopeQuestion = new Question(
            'Domain scopes (domain_id, comma separated, empty = all): ',
            ''
        );
        $scopeInput = $helper->ask($input, $output, $scopeQuestion);
        $scopes = array_filter(array_map('intval', explode(',', $scopeInput)));

        $expireQuestion = new Question(
            'Expires at (YYYY-MM-DD or empty): ',
            ''
        );
        $expireInput = trim((string) $helper->ask($input, $output, $expireQuestion));
        $expiresAt = $expireInput !== ''
            ? new DateTimeImmutable($expireInput)
            : null;

        $confirm = new ConfirmationQuestion('Create token now ? (y/N) ', false);
        if (!$helper->ask($input, $output, $confirm)) {
            $output->writeln('<comment>Aborted.</comment>');
            return Command::SUCCESS;
        }

        $plainToken = 'zmt_' . Uuid::uuid7()->toString() . bin2hex(random_bytes(8));
        $tokenHash = $this->tokenHasher->hash($plainToken);

        $apiToken = new ApiToken(
            name: $name,
            tokenHash: $tokenHash,
            createdByAdmin: $admin
        );
        $apiToken->setExpiresAt($expiresAt);

        $this->entityManager->persist($apiToken);

        foreach ($permissions as $permission) {
            $this->entityManager->persist(
                new ApiTokenPermission($apiToken, $permission)
            );
        }

        foreach ($scopes as $domainId) {
            $this->entityManager->persist(
                new ApiTokenScope($apiToken, $domainId)
            );
        }

        $this->entityManager->flush();

        $output->writeln('');
        $output->writeln('<info>API Token created successfully</info>');
        $output->writeln('----------------------------------');
        $output->writeln('UUID       : ' . $apiToken->getUuid());
        $output->writeln('Name       : ' . $name);
        $output->writeln('Expires at : ' . ($expiresAt?->format('Y-m-d') ?? 'never'));
        $output->writeln('');
        $output->writeln('<comment>⚠️  TOKEN (shown once)</comment>');
        $output->writeln('<fg=yellow>' . $plainToken . '</>');
        $output->writeln('');

        return Command::SUCCESS;
    }


}

?>