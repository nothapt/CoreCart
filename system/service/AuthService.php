<?php
declare(strict_types=1);

namespace CoreCart\System\Service;

use CoreCart\System\Repository\AdminUserRepository;
use CoreCart\System\Engine\RateLimiter;
use CoreCart\System\Entity\AdminUser;
use CoreCart\System\Dto\LoginDTO;

class AuthService
{
    private AdminUserRepository $adminUserRepo;
    private RateLimiter $rateLimiter;

    public function __construct(AdminUserRepository $adminUserRepo, RateLimiter $rateLimiter)
    {
        $this->adminUserRepo = $adminUserRepo;
        $this->rateLimiter = $rateLimiter;
    }

    public function loginAdmin(LoginDTO $dto, string $ipAddress): array
    {
        if ($this->rateLimiter->isLimited($ipAddress, $dto->login)) {
            $remaining = $this->rateLimiter->getRemainingSeconds($ipAddress);
            throw new \RuntimeException("Too many login attempts. Try again in {$remaining} seconds");
        }

        $user = $this->adminUserRepo->verifyPassword($dto->login, $dto->password);

        if (!$user) {
            $this->rateLimiter->recordFailure($ipAddress, $dto->login);
            throw new \RuntimeException('Invalid credentials');
        }

        $this->rateLimiter->recordSuccess($ipAddress, $dto->login);
        $this->adminUserRepo->updateLastLogin((int) $user['admin_id'], $ipAddress);

        return [
            'id'       => (int) $user['admin_id'],
            'username' => $user['username'],
            'email'    => $user['email'],
        ];
    }

    public function getAdminUser(int $id): ?AdminUser
    {
        return $this->adminUserRepo->findById($id);
    }

    public function validateAdminSession(?int $userId): bool
    {
        if (!$userId) {
            return false;
        }

        $user = $this->adminUserRepo->findById($userId);
        return $user !== null && $user->status === 1;
    }
}
