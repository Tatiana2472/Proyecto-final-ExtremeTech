<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * Categoría del catálogo. Una categoría tiene muchos productos.
 */
class Category extends Model
{
    use HasFactory;

    protected $fillable = ['nombre', 'slug', 'descripcion', 'icono', 'activa'];

    protected function casts(): array
    {
        return ['activa' => 'boolean'];
    }

    /**
     * Genera el slug a partir del nombre si no se indicó uno.
     */
    protected static function booted(): void
    {
        static::saving(function (Category $categoria) {
            if (blank($categoria->slug)) {
                $categoria->slug = Str::slug($categoria->nombre);
            }
        });
    }

    /** Una categoría tiene muchos productos. */
    public function productos(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function scopeActivas(Builder $query): Builder
    {
        return $query->where('activa', true);
    }

    /** Se usa el slug en las URLs en lugar del id. */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
