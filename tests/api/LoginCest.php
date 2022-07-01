<?php
namespace App\Tests\api;


use App\Entity\User;
use App\Tests\ApiTester;
use Codeception\Util\HttpCode;
use Faker\Factory;
use Faker\Generator;

class LoginCest
{

    private Generator $faker;
    private string $validEmailAddress;
    private string $validPassword;


    public function _before(ApiTester $I)
    {
        $this->faker = Factory::create();
        $this->validEmailAddress = $this->faker->email();
        $this->validPassword = $this->faker->password();
        $hasher = $I->grabService('security.password_hasher');
        $I->haveInRepository(
            User::class,
            [
                'firstName'    => $this->faker->firstName(),
                'lastName'     => $this->faker->lastName(),
                'email' => $this->validEmailAddress,
                'password' => ''
            ]
        );

        $user = $I->grabEntityFromRepository(
            User::class,
            [
                'email' => $this->validEmailAddress
            ]
        );
        $user->setPassword($hasher->hashPassword($user, $this->validPassword));
    }

    public function loginSuccessfully(ApiTester $I)
    {
        $I->sendPost(
            '/login',
            [
                'emailAddress' => $this->validEmailAddress,
                'password' => $this->validPassword
            ]
        );

        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseMatchesJsonType(
            [
                'token' => 'string:!empty'
            ]
        );
    }

    public function verifyReturnedAPITokenIsValid(ApiTester $I)
    {
        $I->sendPost(
            '/login',
            [
                'emailAddress' => $this->validEmailAddress,
                'password'     => $this->validPassword
            ]
        );

        $token = $I->grabDataFromResponseByJsonPath('token')[0];

        $I->seeInRepository(
            User::class,
            [
                'email'    => $this->validEmailAddress,
                'apiToken' => $token
            ]
        );
    }

    public function loginWithUnknownEmailAddressAndFail(ApiTester $I)
    {
        $I->sendPost(
            '/login',
            [
                'emailAddress' => 'unknown@test.com',
                'password'     => $this->validPassword
            ]
        );

        $I->seeResponseCodeIs(HttpCode::UNAUTHORIZED);
        $I->seeResponseContains('"error":"Invalid login credentials provided"');
    }
}
