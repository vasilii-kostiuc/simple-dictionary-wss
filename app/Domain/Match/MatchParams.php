<?php

namespace App\Domain\Match;

use App\Domain\MatchMaking\Enums\MatchType;

final class MatchParams
{
    public function __construct(
        public readonly MatchType $matchType,
        public readonly int $languageFromId,
        public readonly int $languageToId,
        public readonly array $matchTypeParams,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            matchType: MatchType::from($data['match_type']),
            languageFromId: $data['language_from_id'],
            languageToId: $data['language_to_id'],
            matchTypeParams: $data['match_type_params'],
        );
    }

    public function toArray(): array
    {
        return [
            'match_type' => $this->matchType->value,
            'language_from_id' => $this->languageFromId,
            'language_to_id' => $this->languageToId,
            'match_type_params' => $this->matchTypeParams,
        ];
    }
}
