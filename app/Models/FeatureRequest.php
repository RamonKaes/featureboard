<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\FeatureRequestSort;
use App\Enums\FeatureRequestStatus;
use Database\Factories\FeatureRequestFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $product_id
 * @property int|null $user_id
 * @property string $title
 * @property string $description
 * @property FeatureRequestStatus $status
 * @property int $votes_count
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['title', 'description'])]
#[Hidden(['search_vector'])]
class FeatureRequest extends Model
{
    /** @use HasFactory<FeatureRequestFactory> */
    use HasFactory;

    private const int MIN_PREFIX_LENGTH = 3;

    /**
     * The model's default values for attributes.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'new',
        'votes_count' => 0,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => FeatureRequestStatus::class,
            'votes_count' => 'integer',
        ];
    }

    /**
     * Add a has_voted attribute that tells whether the given user has voted.
     *
     * @param  Builder<self>  $query
     */
    public function scopeWithVoteOf(Builder $query, ?User $user): void
    {
        $query->withExists(['votes as has_voted' => fn (Builder $votes) => $votes->where('user_id', $user?->id)]);
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeSortedBy(Builder $query, FeatureRequestSort $sort): void
    {
        $query->orderByDesc($sort->column())->orderByDesc('id');
    }

    /**
     * Find requests sharing at least one word with the text, the most relevant first.
     *
     * @param  Builder<self>  $query
     */
    public function scopeSimilarTo(Builder $query, string $text): void
    {
        $tsQuery = $this->toTsQuery($text);

        $query->whereRaw("search_vector @@ to_tsquery('german', ?)", [$tsQuery])
            ->orderByRaw("ts_rank(search_vector, to_tsquery('german', ?)) DESC", [$tsQuery])
            ->orderByDesc('votes_count')
            ->orderByDesc('id');
    }

    /**
     * Build an OR query from plain words, so search operators in the input have no effect.
     * The last word may still be incomplete while typing, so it also matches as a prefix.
     */
    private function toTsQuery(string $text): string
    {
        preg_match_all('/[\p{L}\p{N}]+/u', $text, $matches);

        $words = $matches[0];
        $last = array_key_last($words);

        if ($last !== null && mb_strlen($words[$last]) >= self::MIN_PREFIX_LENGTH) {
            $words[$last] .= ':*';
        }

        return implode(' | ', $words);
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * The author is null once the account has been deleted.
     *
     * @return BelongsTo<User, $this>
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @return HasMany<Vote, $this>
     */
    public function votes(): HasMany
    {
        return $this->hasMany(Vote::class);
    }
}
