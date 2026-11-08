<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminProductImagesTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $u = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        Admin::create(['user_id' => $u->id, 'role' => 'super_admin']);

        return $u;
    }

    private function payload(Category $cat, array $extra = []): array
    {
        return array_merge([
            'category_id' => $cat->id, 'name_en' => 'Sw', 'name_ar' => 'مفتاح',
            'price' => 10, 'stock_quantity' => 5,
        ], $extra);
    }

    public function test_insert_then_replace_and_remove_keeps_cover_and_files_consistent(): void
    {
        Storage::fake('public');
        $cat = Category::create(['name_en' => 'Cat', 'name_ar' => 'قسم', 'slug' => 'cat', 'is_active' => true]);
        $admin = $this->admin();

        $this->actingAs($admin)->post('/admin/products', $this->payload($cat, [
            'images' => [UploadedFile::fake()->image('a.jpg'), UploadedFile::fake()->image('b.jpg')],
            'thumbnail_index' => 1,
        ]))->assertRedirect();

        $p = Product::first();
        $this->assertCount(2, $p->images);
        $this->assertNotEmpty($p->image_path);

        // pick the other gallery image as the cover: image_path must follow
        $first = $p->images->firstWhere('path', '!=', $p->image_path);
        $this->actingAs($admin)->put("/admin/products/{$p->id}", $this->payload($cat, [
            'thumbnail_source' => 'existing', 'thumbnail_existing_id' => $first->id,
        ]))->assertRedirect();
        $p->refresh();
        $this->assertSame($first->path, $p->image_path);

        // replacing the main image must not delete a file a gallery row still uses
        $oldPath = $p->image_path;
        $this->actingAs($admin)->put("/admin/products/{$p->id}", $this->payload($cat, [
            'image' => UploadedFile::fake()->image('main.jpg'),
        ]))->assertRedirect();
        $p->refresh();
        $this->assertNotSame($oldPath, $p->image_path);
        Storage::disk('public')->assertExists($oldPath);
        Storage::disk('public')->assertExists($p->image_path);

        // removing the gallery image that is the cover must not leave a dangling cover
        $p->update(['image_path' => $first->path]);
        $this->actingAs($admin)->put("/admin/products/{$p->id}", $this->payload($cat, [
            'remove_images' => [$first->id],
            'thumbnail_source' => 'existing', 'thumbnail_existing_id' => $first->id,
        ]))->assertRedirect();
        $p->refresh();
        $this->assertNotSame($first->path, $p->image_path);
        $this->assertNotNull($p->image_path);
        Storage::disk('public')->assertExists($p->image_path);
        $this->assertSame(1, ProductImage::where('product_id', $p->id)->count());
    }
}
