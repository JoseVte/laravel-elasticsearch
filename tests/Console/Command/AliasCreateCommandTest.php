<?php

declare(strict_types=1);

namespace MailerLite\LaravelElasticsearch\Tests\Console\Command;

use Exception;
use Generator;
use Mockery\MockInterface;
use Elastic\Elasticsearch\Client;
use Elastic\Elasticsearch\Endpoints\Indices;
use MailerLite\LaravelElasticsearch\Tests\TestCase;

final class AliasCreateCommandTest extends TestCase
{
    public function testAliasCreateMustSucceed(): void
    {
        $this->mock(Client::class, function (MockInterface $mock) {
            $mock->expects('indices')
                ->times(2)
                ->andReturns(
                    $this->mock(Indices::class, function (MockInterface $mock) {
                        $mock->expects('exists')
                            ->andReturns(true);

                        $mock->expects('putAlias')
                            ->andReturns([]);
                    })
                );
        });

        $this->artisan(
            'laravel-elasticsearch:utils:alias-create',
            [
                'index-name' => 'valid_index_name',
                'alias-name' => 'valid_alias_name',
            ]
        )->expectsOutput('Alias valid_alias_name created for index valid_index_name.')
            ->assertExitCode(0);
    }

    public function testAliasCreateMustFail(): void
    {
        $this->mock(Client::class, function (MockInterface $mock) {
            $mock->expects('indices')
                ->times(2)
                ->andReturns(
                    $this->mock(Indices::class, function (MockInterface $mock) {
                        $mock->expects('exists')
                            ->andReturns(true);

                        $mock->expects('putAlias')
                            ->andThrow(
                                new Exception('error creating alias test exception')
                            );
                    })
                );
        });

        $this->artisan(
            'laravel-elasticsearch:utils:alias-create',
            [
                'index-name' => 'valid_index_name',
                'alias-name' => 'valid_alias_name',
            ]
        )->assertExitCode(1)
            ->expectsOutput('Error creating alias valid_alias_name for index valid_index_name, exception message: error creating alias test exception.');
    }

    public function testAliasCreateMustFailBecauseIndexDoesntExists(): void
    {
        $this->mock(Client::class, function (MockInterface $mock) {
            $mock->expects('indices')
                ->andReturns(
                    $this->mock(Indices::class, function (MockInterface $mock) {
                        $mock->expects('exists')
                            ->andReturns(false);

                        $mock->allows('putAlias')->never();
                    })
                );
        });

        $this->artisan(
            'laravel-elasticsearch:utils:alias-create',
            [
                'index-name' => 'valid_index_name',
                'alias-name' => 'valid_alias_name',
            ]
        )->assertExitCode(1)
            ->expectsOutput('Index valid_index_name doesn\'t exists and alias cannot be created.');
    }

    /**
     * @dataProvider invalidIndexNameDataProvider
     */
    public function testArgumentIndexNameAndAliasAreInValid(
        $invalidIndexName,
        $invalidAliasName,
        string $expectedOutputMessage
    ): void {
        $this->artisan(
            'laravel-elasticsearch:utils:alias-create',
            [
                'index-name' => $invalidIndexName,
                'alias-name' => $invalidAliasName,
            ]
        )->assertExitCode(1)
            ->expectsOutput($expectedOutputMessage);
    }

    public static function invalidIndexNameDataProvider(): Generator
    {
        yield [
            null,
            'valid_alias_name',
            'Argument index-name must be a non empty string.',
        ];

        yield [
            '',
            'valid_alias_name',
            'Argument index-name must be a non empty string.',
        ];

        yield [
            true,
            'valid_alias_name',
            'Argument index-name must be a non empty string.',
        ];

        yield [
            1,
            'valid_alias_name',
            'Argument index-name must be a non empty string.',
        ];

        yield [
            [],
            'valid_alias_name',
            'Argument index-name must be a non empty string.',
        ];

        yield [
            'valid_index_name',
            null,
            'Argument alias-name must be a non empty string.',
        ];

        yield [
            'valid_index_name',
            '',
            'Argument alias-name must be a non empty string.',
        ];

        yield [
            'valid_index_name',
            true,
            'Argument alias-name must be a non empty string.',
        ];

        yield [
            'valid_index_name',
            1,
            'Argument alias-name must be a non empty string.',
        ];

        yield [
            'valid_index_name',
            [],
            'Argument alias-name must be a non empty string.',
        ];
    }
}
