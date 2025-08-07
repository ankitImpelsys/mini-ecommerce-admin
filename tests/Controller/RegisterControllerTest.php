<?php

namespace App\Tests\Controller;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class RegisterControllerTest extends WebTestCase
{
    private $client;
    private $em;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->em = static::getContainer()->get('doctrine')->getManager();

        // Clean up any test users
        $user = $this->em->getRepository(User::class)->findOneBy(['email' => 'test@example.com']);
        if ($user) {
            $this->em->remove($user);
            $this->em->flush();
        }
    }

    public function testSuccessfulRegistration(): void
    {
        $crawler = $this->client->request('POST', '/register', [
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
        // Create a user manually
        $user = new User();
        $user->setEmail('duplicate_' . uniqid() . '@example.com');
        $user->setPassword('dummy');
        $user->setRoles(['ROLE_ADMIN']);
        $this->em->persist($user);
        $this->em->flush();

        $crawler = $this->client->request('POST', '/register', [
            'email' => 'duplicate@example.com',
            'password' => 'Valid@123',
            'confirm_password' => 'Valid@123',
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('.error', 'Email already in use.');
    }

    public function testPasswordTooShort(): void
    {
        $this->client->request('POST', '/register', [
            'email' => 'shortpass@example.com',
            'password' => 'A@1',
            'confirm_password' => 'A@1',
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('.error', 'Password must be at least 6 characters.');
    }

    public function testPasswordMissingUppercase(): void
    {
        $this->client->request('POST', '/register', [
            'email' => 'noupcase@example.com',
            'password' => 'valid@123',
            'confirm_password' => 'valid@123',
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('.error', 'Password must contain at least one uppercase letter.');
    }

    public function testPasswordsDoNotMatch(): void
    {
        $this->client->request('POST', '/register', [
            'email' => 'mismatch@example.com',
            'password' => 'Valid@123',
            'confirm_password' => 'Mismatch@123',
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('.error', 'Passwords do not match.');
    }
}
