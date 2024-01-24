<?php

declare(strict_types=1);

namespace MailerLite\LaravelElasticsearch\Tests\Console\Command;

use Elastic\Elasticsearch\Endpoints\Indices;
use Exception;
use Generator;
use Mockery\MockInterface;
use Elastic\Elasticsearch\Client;
use MailerLite\LaravelElasticsearch\Tests\TestCase;

final class AliasSwitchIndexCommandTest extends TestCase
{
    public function testSwitchIndexMustSucceed(): void
    {
        $this->mock(Client::class, function (MockInterface $mock) {
            $mock->expects('indices')
                ->times(3)
                ->andReturns(
                    $this->mock(Indices::class, function (MockInterface $mock) {
                        $mock->expects('exists')
                            ->andReturns(true);

                        $mock->expects('putAlias')
                            ->andReturns([]);

                        $mock->expects('deleteAlias')
                            ->andReturns([]);
                    })
                );
        });

        $this->artisan(
            'laravel-elasticsearch:utils:alias-switch-index',
            [
                'new-index-name' => 'new_valid_index_name',
                'old-index-name' => 'old_valid_index_name',
                'alias-name' => 'valid_alias_name',
            ]
        )->assertExitCode(0)
            ->expectsOutput(
                'New index new_valid_index_name linked and old index old_valid_index_name removed from alias valid_alias_name.'
            );
    }

    public function testSwitchIndexMustFailBecauseNewIndexDoesntExists(): void
    {
        $this->mock(Client::class, function (MockInterface $mock) {
            $mock->expects('indices')
                ->andReturns(
                    $this->mock(Indices::class, function (MockInterface $mock) {
                        $mock->expects('exists')
                            ->andReturns(false);

                        $mock->allows('putAlias')->never();

                        $mock->allows('deleteAlias')->never();
                    })
                );
        });

        $this->artisan(
            'laravel-elasticsearch:utils:alias-switch-index',
            [
                'new-index-name' => 'new_valid_index_name',
                'old-index-name' => 'old_valid_index_name',
                'alias-name' => 'valid_alias_name',
            ]
        )->assertExitCode(1)
            ->expectsOutput(
                'Index new_valid_index_name cannot be linked to alias because doesn\'t exists.'
            );
    }

    public function testSwitchIndexMustFailDueToPutAliasException(): void
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
                                new Exception(
                                    'error adding new index to alias exception'
                                )
                            );

                        $mock->allows('deleteAlias')->never();
                    })
                );
        });

        $this->artisan(
            'laravel-elasticsearch:utils:alias-switch-index',
            [
                'new-index-name' => 'new_valid_index_name',
                'old-index-name' => 'old_valid_index_name',
                'alias-name' => 'valid_alias_name',
            ]
        )->assertExitCode(1)
            ->expectsOutput(
                'Error switching indexes - new index: new_valid_index_name, old index: old_valid_index_name in alias valid_alias_name, exception message: error adding new index to alias exception.'
            );
    }

    public function testSwitchIndexMustFailDueToDeleteAliasException(): void
    {
        $this->mock(Client::class, function (MockInterface $mock) {
            $mock->expects('indices')
                ->times(3)
                ->andReturns(
                    $this->mock(Indices::class, function (MockInterface $mock) {
                        $mock->expects('exists')
                            ->andReturns(true);

                        $mock->expects('putAlias')
                            ->andReturns([]);

                        $mock->expects('deleteAlias')
                            ->andThrow(
                                new Exception(
                                    'error removing old index from alias exception'
                                )
                            );
                    })
                );
        });

        $this->artisan(
            'laravel-elasticsearch:utils:alias-switch-index',
            [
                'new-index-name' => 'new_valid_index_name',
                'old-index-name' => 'old_valid_index_name',
                'alias-name' => 'valid_alias_name',
            ]
        )->assertExitCode(1)
            ->expectsOutput(
                'Error switching indexes - new index: new_valid_index_name, old index: old_valid_index_name in alias valid_alias_name, exception message: error removing old index from alias exception.'
            );
    }

    /**
     * @dataProvider invalidIndexNameDataProvider
     */
    public function testArgumentIndexNameAndAliasAreInValid(
        $invalidNewIndexName,
        $invalidOldIndexName,
        $invalidAliasName,
        string $expectedOutputMessage
    ): void {
        $this->artisan(
            'laravel-elasticsearch:utils:alias-switch-index',
            [
                'new-index-name' => $invalidNewIndexName,
                'old-index-name' => $invalidOldIndexName,
                'alias-name' => $invalidAliasName,
            ]
        )->assertExitCode(1)
            ->expectsOutput($expectedOutputMessage);
    }

    public static function invalidIndexNameDataProvider(): Generator
    {
        yield [
            null,
            'valid_old_index_name',
            'valid_alias_name',
            'Argument new-index-name must be a non empty string.',
        ];

        yield [
            '',
            'valid_old_index_name',
            'valid_alias_name',
            'Argument new-index-name must be a non empty string.',
        ];

        yield [
            true,
            'valid_old_index_name',
            'valid_alias_name',
            'Argument new-index-name must be a non empty string.',
        ];

        yield [
            1,
            'valid_old_index_name',
            'valid_alias_name',
            'Argument new-index-name must be a non empty string.',
        ];

        yield [
            [],
            'valid_old_index_name',
            'valid_alias_name',
            'Argument new-index-name must be a non empty string.',
        ];

        yield [
            'valid_new_index_name',
            null,
            'valid_alias_name',
            'Argument old-index-name must be a non empty string.',
        ];

        yield [
            'valid_new_index_name',
            '',
            'valid_alias_name',
            'Argument old-index-name must be a non empty string.',
        ];

        yield [
            'valid_new_index_name',
            true,
            'valid_alias_name',
            'Argument old-index-name must be a non empty string.',
        ];

        yield [
            'valid_new_index_name',
            1,
            'valid_alias_name',
            'Argument old-index-name must be a non empty string.',
        ];

        yield [
            'valid_new_index_name',
            [],
            'valid_alias_name',
            'Argument old-index-name must be a non empty string.',
        ];

        yield [
            'valid_new_index_name',
            'valid_old_index_name',
            null,
            'Argument alias-name must be a non empty string.',
        ];

        yield [
            'valid_new_index_name',
            'valid_old_index_name',
            '',
            'Argument alias-name must be a non empty string.',
        ];

        yield [
            'valid_new_index_name',
            'valid_old_index_name',
            true,
            'Argument alias-name must be a non empty string.',
        ];

        yield [
            'valid_new_index_name',
            'valid_old_index_name',
            1,
            'Argument alias-name must be a non empty string.',
        ];

        yield [
            'valid_new_index_name',
            'valid_old_index_name',
            [],
            'Argument alias-name must be a non empty string.',
        ];
    }
}
