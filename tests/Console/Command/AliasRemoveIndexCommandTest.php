<?php

declare(strict_types=1);

namespace MailerLite\LaravelElasticsearch\Tests\Console\Command;

use Elastic\Elasticsearch\Endpoints\Indices;
use Exception;
use Generator;
use Mockery\MockInterface;
use Elastic\Elasticsearch\Client;
use MailerLite\LaravelElasticsearch\Tests\TestCase;

final class AliasRemoveIndexCommandTest extends TestCase
{
    public function testAliasRemoveMustSucceed(): void
    {
        $this->mock(Client::class, function (MockInterface $mock) {
            $mock->expects('indices')
                ->times(2)
                ->andReturns(
                    $this->mock(Indices::class, function (MockInterface $mock) {
                        $mock->expects('exists')
                            ->andReturns(true);

                        $mock->expects('deleteAlias')
                            ->andReturn([]);
                    })
                );
        });

        $this->artisan(
            'laravel-elasticsearch:utils:alias-remove-index',
            [
                'index-name' => 'valid_index_name',
                'alias-name' => 'valid_alias_name',
            ]
        )->assertExitCode(0)
            ->expectsOutput('Index valid_index_name removed from alias valid_alias_name.');
    }

    public function testAliasRemoveMustFail(): void
    {
        $this->mock(Client::class, function (MockInterface $mock) {
            $mock->expects('indices')
                ->times(2)
                ->andReturns(
                    $this->mock(Indices::class, function (MockInterface $mock) {
                        $mock->expects('exists')
                            ->andReturns(true);

                        $mock->expects('deleteAlias')
                            ->andThrow(
                                new Exception('error removing index from alias exception')
                            );
                    })
                );
        });

        $this->artisan(
            'laravel-elasticsearch:utils:alias-remove-index',
            [
                'index-name' => 'valid_index_name',
                'alias-name' => 'valid_alias_name',
            ]
        )->assertExitCode(1)
            ->expectsOutput(
                'Error removing index valid_index_name from alias valid_alias_name, exception message: error removing index from alias exception.'
            );
    }

    public function testAliasRemoveMustFailBecauseIndexDoesntExists(): void
    {
        $this->mock(Client::class, function (MockInterface $mock) {
            $mock->expects('indices')
                ->andReturns(
                    $this->mock(Indices::class, function (MockInterface $mock) {
                        $mock->expects('exists')
                            ->andReturns(false);

                        $mock->allows('deleteAlias')->never();
                    })
                );
        });

        $this->artisan(
            'laravel-elasticsearch:utils:alias-remove-index',
            [
                'index-name' => 'valid_index_name',
                'alias-name' => 'valid_alias_name',
            ]
        )->assertExitCode(1)
            ->expectsOutput(
                'Index valid_index_name doesn\'t exists and cannot be removed from alias.'
            );
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
            'laravel-elasticsearch:utils:alias-remove-index',
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
