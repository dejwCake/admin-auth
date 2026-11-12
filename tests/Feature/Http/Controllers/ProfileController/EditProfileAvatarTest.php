<?php

declare(strict_types=1);

namespace Brackets\AdminAuth\Tests\Feature\Http\Controllers\ProfileController;

use Brackets\AdminAuth\Tests\AdminUserTestCase;
use Brackets\AdminAuth\Tests\Models\TestMediaAdminUserModel;
use Brackets\Media\MediaServiceProvider;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Application;
use Illuminate\Http\UploadedFile;
use Override;
use Spatie\MediaLibrary\MediaLibraryServiceProvider;

class EditProfileAvatarTest extends AdminUserTestCase
{
    public function testAvatarAndCollectionLimitsArePassedToTheForm(): void
    {
        $user = $this->createTestUser();
        $user->addMedia(UploadedFile::fake()->create('avatar.pdf', 10, 'application/pdf'))
            ->toMediaCollection('avatar');

        $response = $this->actingAs($user, $this->adminAuthGuard)->get('/admin/profile');

        $response->assertStatus(200);
        $media = $response->viewData('avatarMedia');
        self::assertCount(1, $media);
        self::assertSame('avatar.pdf', $media->first()['name']);
        $response->assertSee('&quot;collection&quot;:&quot;avatar&quot;', false);
        $response->assertSee('&quot;maxNumberOfFiles&quot;:1', false);
        $response->assertSee('&quot;maxFileSizeInMb&quot;:5.0', false);
        $response->assertSee('&quot;url&quot;:&quot;http:\/\/localhost\/upload&quot;', false);
    }

    /**
     * @param Application $app
     */
    #[Override]
    protected function getPackageProviders($app): array
    {
        return [
            ...parent::getPackageProviders($app),
            MediaLibraryServiceProvider::class,
            MediaServiceProvider::class,
        ];
    }

    /**
     * @param Application $app
     */
    #[Override]
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('auth.providers.admin_users.model', TestMediaAdminUserModel::class);
    }

    #[Override]
    protected function setUpDatabase(Application $app): void
    {
        parent::setUpDatabase($app);

        $app['db']->connection()->getSchemaBuilder()->create('media', static function (Blueprint $table): void {
            $table->id();
            $table->morphs('model');
            $table->uuid()->nullable()->unique();
            $table->string('collection_name');
            $table->string('name');
            $table->string('file_name');
            $table->string('mime_type')->nullable();
            $table->string('disk');
            $table->string('conversions_disk')->nullable();
            $table->unsignedBigInteger('size');
            $table->json('manipulations');
            $table->json('custom_properties');
            $table->json('generated_conversions');
            $table->json('responsive_images');
            $table->unsignedInteger('order_column')->nullable()->index();
            $table->nullableTimestamps();
        });
    }

    private function createTestUser(): TestMediaAdminUserModel
    {
        $this->app->make(Gate::class)->define('admin', static fn (): bool => true);

        return TestMediaAdminUserModel::create([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
            'password' => $this->app->make(Hasher::class)->make('testpass123'),
            'activated' => true,
            'forbidden' => false,
            'language' => 'en',
        ]);
    }
}
