<?php

namespace App\Command;

use App\Platform\Entity\AdminUser;
use Doctrine\ORM\EntityManagerInterface;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;

use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: "app:create-super-admin",
    description: "Create first super admin"
)]
final class CreateSuperAdminCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var QuestionHelper $helper */
        $helper = $this->getHelper('question');

        $existingSuperAdmins = $this->entityManager
            ->getRepository(AdminUser::class)
            ->count(['roles' => ['ROLE_SUPER_ADMIN']]);

        if ($existingSuperAdmins > 0) {
            $confirm = new  ConfirmationQuestion(
                '<comment>A super admin already exists. Do you want to create another one ? (y/N)</comment> ',
                false
            );

            if (!$helper->ask($input, $output, $confirm)) {
                $output->writeln('<info>Aborted.</info>');
                return Command::SUCCESS;
            }
        }

        $emailQuestion = new Question('Super admin email: ');
        $emailQuestion->setValidator(function (?string $value) {
            if (!$value || !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                throw new \RuntimeException('Please enter a valid email address.');
            }
            return strtolower(trim($value));
        });
        $emailQuestion->setMaxAttempts(3);

        $email = $helper->ask($input, $output, $emailQuestion);

        $passwordQuestion = new Question('Password (input hidden): ');
        $passwordQuestion->setHidden(true);
        $passwordQuestion->setHiddenFallback(false);
        $passwordQuestion->setValidator(function (?string $value) {
            if (!$value || strlen($value) < 8) {
                throw new \RuntimeException('Password must be at least 8 characters long.');
            }
            return $value;
        });
        $passwordQuestion->setMaxAttempts(3);

        $plainPassword = $helper->ask($input, $output, $passwordQuestion);

        $admin = new AdminUser();
        $admin->setEmail($email);
        $admin->setRoles(['ROLE_SUPER_ADMIN']);
        $admin->setActive(true);

        $hashedPassword = $this->passwordHasher->hashPassword($admin, $plainPassword);
        $admin->setPassword($hashedPassword);

        $this->entityManager->persist($admin);
        $this->entityManager->flush();

        $output->writeln('');
        $output->writeln('<info>Super admin successfully created.</info>');
        $output->writeln(sprintf('Email : <comment>%s</comment>', $admin->getEmail()));
        $output->writeln(sprintf('UUID : <comment>%s</comment>', $admin->getUuid()));

        return Command::SUCCESS;
    }
}


?>