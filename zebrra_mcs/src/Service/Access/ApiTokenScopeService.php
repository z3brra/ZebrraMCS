<?php

namespace App\Service\Access;

use App\Http\Error\ApiException;
use App\Security\ApiTokenUser;

final class ApiTokenScopeService
{
    /**
     * @param list<int> $allowedDomainIds
     */
    public function denyUnlessDomainAllowed(ApiTokenUser $tokenUser, string $targetDomainUuid): void
    {
        $allowedDomainIds = $tokenUser->getScopedDomainUuids();

        if ($allowedDomainIds === []) {
            return;
        }

        if (!in_array($targetDomainUuid, $allowedDomainIds, true)) {
            throw ApiException::scopeViolation(
                message: 'Scope violation',
                details: [
                    'targetDomainUuid' => $targetDomainUuid,
                    'allowedDomainIds' => $allowedDomainIds,
                ]
            );
        }
    }
}

?>