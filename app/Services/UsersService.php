<?php

namespace App\Services;

use App\Dtos\CreateUserDto;
use App\Dtos\QueryUserDto;
use App\Models\User;
use App\Services\External\HackerNewsService;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class UsersService
{
    public function __construct(
        private User $userRepo,
        private HackerNewsService $hackerNewsService,
    ) {
    }

    function create(CreateUserDto $createUserDto): User
    {
        $user = $this->userRepo->where('hacker_news_id', $createUserDto->hackerNewsId)->first();

        if ($user) {
            return $user;
        }

        return $this->userRepo->create($createUserDto->toArray());
    }

    function findOneById(int $id): User
    {
        $user =  $this->userRepo->find($id);

        if (!$user) {
            throw new NotFoundHttpException('User not found');
        }

        return $user;
    }

    /**
     * @return User[]
     */
    function findBy(QueryUserDto $query): array
    {
        return $this->userRepo->where($query->toArray())->get();
    }

    function findOneBy(QueryUserDto $query): ?User
    {
        return $this->userRepo->where($query->toArray())->first();
    }

    function findByHackerNewsIdOrCreate(string $by): User
    {
        $user = $this->userRepo->where('hacker_news_id', $by)->first();

        if (!$user) {
            $hackerNewsUser = $this->hackerNewsService->getUser($by);

            $user = $this->create(new CreateUserDto(
                $hackerNewsUser['id'],
                $hackerNewsUser['created'],
                $hackerNewsUser['karma'],
                $hackerNewsUser['about'] ?? '',
                json_encode($hackerNewsUser['submitted'] ?? (object) []),
            ));
        }

        return $user;
    }
}
