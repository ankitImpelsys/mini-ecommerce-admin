<?php

namespace App\Tests\Controller;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use App\Tests\DataProvider\RegisterDataProvider;
use PHPUnit\Framework\Attributes\DataProvider;

class RegisterControllerTest extends WebTestCase
{
    private $client;
    private $em;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->em = static::getContainer()->get('doctrine')->getManager();

        $user = $this->em->getRepository(User::class)->findOneBy(['email' => 'test@example.com']);
        if ($user) {
            $this->em->remove($user);
            $this->em->flush();
        }
    }

    public function testSuccessfulRegistration(): void
    {
        $this->client->request('POST', '/register', [
            'email' => 'test@example.com',
            'password' => 'Valid@123',
            'confirm_password' => 'Valid@123',
        ]);

        $this->assertResponseRedirects('/login');
        $this->client->followRedirect();
        $this->assertSelectorExists('.flash-success');
    }

    public function testEmailAlreadyExists(): void
    {
        $existing = $this->em->getRepository(User::class)->findOneBy(['email' => 'duplicate@example.com']);
        if ($existing) {
            $this->em->remove($existing);
            $this->em->flush();
        }

        $user = new User();
        $user->setEmail('duplicate@example.com');
        $user->setPassword('dummy');
        $user->setRoles(['ROLE_ADMIN']);
        $this->em->persist($user);
        $this->em->flush();

        $this->client->request('POST', '/register', [
            'email' => 'duplicate@example.com',
            'password' => 'Valid@123',
            'confirm_password' => 'Valid@123',
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('.error', 'Email already in use.');
    }

    public static function provideInvalidPasswords(): array
    {
        return RegisterDataProvider::invalidPasswords();
    }

    #[DataProvider('provideInvalidPasswords')]
    public function testInvalidPasswords(string $email, string $password, string $confirmPassword, string $expectedError): void
    {
        $this->client->request('POST', '/register', [
            'email' => $email,
            'password' => $password,
            'confirm_password' => $confirmPassword,
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('.error', $expectedError);
    }
}
