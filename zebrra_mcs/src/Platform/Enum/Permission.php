<?php

namespace App\Platform\Enum;

enum Permission: string
{
    // Domains
    case DOMAINS_READ = 'domains.read';
    case DOMAINS_CREATE = 'domains.create';
    case DOMAINS_DISABLE = 'domains.disable';
    case DOMAINS_ENABLE = 'domains.enable';

    // Users
    case USERS_READ = 'users.read';
    case USERS_CREATE = 'users.create';
    case USERS_DISABLE = 'users.disable';
    case USERS_ENABLE = 'users.enable';
    case USERS_UPDATE_PASSWORD = 'users.update_password';

    // Aliases
    case ALIASES_READ = 'aliases.read';
    case ALIASES_CREATE = 'aliases.create';
    case ALIASES_DELETE = 'aliases.delete';

    // Mail
    case MAIL_SEND = 'mail.send';

    // Audit
    case AUDIT_READ = 'audit.read';
}

?>