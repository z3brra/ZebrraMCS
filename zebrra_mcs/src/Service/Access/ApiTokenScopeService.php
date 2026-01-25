<?php

namespace App\Service\Access;

use App\Http\Error\ApiException;
use App\Security\ApiTokenUser;

final class ApiTokenScopeService
{
    /**
     * @param list<int> $allowedDomainIds
     */
    public function denyUnlessDomainAllowed(ApiTokenUser $tokenUser, int $targetDomainId): void
    {
        $allowedDomainIds = $tokenUser->getScopedDomainIds();

        if ($allowedDomainIds === []) {
            return;
        }

        if (!in_array($targetDomainId, $allowedDomainIds, true)) {
            throw ApiException::scopeViolation(
                message: 'Scope violation',
                details: [
                    'targetDomainId' => $targetDomainId,
                    'allowedDomainIds' => $allowedDomainIds,
                ]
            );
        }
    }
}

?>