<?php

namespace haddowg\JsonApiOpenApi\Descriptors\Validation;

use haddowg\JsonApiOpenApi\Descriptors\Route;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use LaravelJsonApi\Validation\Rules\AllowedFieldSets;
use LaravelJsonApi\Validation\Rules\AllowedFilterParameters;
use LaravelJsonApi\Validation\Rules\AllowedIncludePaths;
use LaravelJsonApi\Validation\Rules\AllowedSortParameters;
use LaravelJsonApi\Validation\Rules\ParameterNotSupported;

class ValidationRules
{
    /**
     * @var Collection<string, array<string|Rule|ValidationRule>>
     */
    private Collection $rules;

    public function __construct(private readonly Route $route)
    {
        $request = $this->route->request();

        try {
            /** @var array<string, string|Rule|ValidationRule|array<string|Rule|ValidationRule>> $rules */
            $rules = $request && method_exists($request, 'rules') ? $request->rules() : [];
            $this->rules = collect(self::normalise($rules));
        } catch (\Throwable) {
            $this->rules = Collection::empty();
        }
    }

    public static function make(Route $route): self
    {
        return new self($route);
    }

    /**
     * @param array<string, string|Rule|ValidationRule|array<string|Rule|ValidationRule>> $rules
     * @return array<string, array<string|Rule|ValidationRule>>
     * @phpstan-ignore-next-line due to Rule deprecation
     */
    public static function normalise(array $rules): array
    {
        /**
         * @var Collection<string, array<string|Rule|ValidationRule>> $normalized
         */
        $normalized = Collection::empty();
        collect($rules)
            ->map(fn ($rules) => is_string($rules) ? explode('|', $rules) : Arr::wrap($rules))
            ->each(function ($fieldRules, $field) use ($normalized) {
                $field = Str::of($field);
                if ($field->contains('.')) {
                    $required = collect($fieldRules)->contains('required');
                    $parts = $field->explode('.');
                    while ($parts->count() > 1) {
                        $parts->pop();

                        $key = $parts->implode('.');

                        if (!$normalized->has($key)) {
                            $normalized->put($key, array_filter(['array', $required ? 'required' : null]));
                        } elseif ($required) {
                            $normalized->put($key, collect($normalized->get($key))->push('required')->unique()->all());
                        }
                    }
                }
                $normalized->put($field->toString(), $fieldRules);
            });

        return $normalized->all();
    }

    /**
     * @return array<string, array<string|Rule|ValidationRule>>
     * @phpstan-ignore-next-line due to Rule deprecation
     */
    public function all(): array
    {
        return $this->rules->all();
    }

    /**
     * Returns the filters that are allowed and validated by the request.
     *
     * @return Collection<string,array<string|Rule|ValidationRule>>|null returns null if there are no relevant rules, otherwise a collection of filter keys and their rules
     * @phpstan-ignore-next-line due to Rule deprecation
     */
    public function filters(): ?Collection
    {
        if (!in_array('GET', $this->route->methods())) {
            return null;
        }

        if ($this->isNotSupported('filter')) {
            return null;
        }

        // @phpstan-ignore-next-line phpstan infers the type from the initial collection
        return collect(array_fill_keys($this->allowedFilters(), []))->merge($this->validatedFilters())
            ->map(fn ($rules) => collect($rules)->map(fn ($rule) => is_string($rule) ? str_replace('filter.', '', $rule) : $rule)->all());
    }

    /**
     * @return Collection<string,array<string|Rule|ValidationRule>>|null
     * @phpstan-ignore-next-line due to Rule deprecation
     */
    public function pagination(): ?Collection
    {
        if (!$this->route->isFetchingMany()) {
            return null;
        }

        return $this->rules->filter(fn ($rules, $key) => Str::startsWith($key, 'page.'))
            ->mapWithKeys(fn ($rules, $key) => [Str::replace('page.', '', $key) => $rules]);
    }

    /**
     * @return Collection<int, string>|null
     */
    public function sortable(): ?Collection
    {
        if (!in_array('GET', $this->route->methods())) {
            return null;
        }

        if ($this->isNotSupported('sort')) {
            return null;
        }

        $sortRules = collect($this->rules->get('filter', []));
        /** @var ?AllowedSortParameters $allowedRule */
        $allowedRule = $sortRules->first(fn ($rule) => $rule instanceof AllowedSortParameters);

        return $allowedRule ? AllowedRule::make($allowedRule)->getAllowed() : Collection::empty();
    }

    /**
     * @return Collection<int, string>|null
     */
    public function includes(): ?Collection
    {
        if ($this->isNotSupported('includes')) {
            return null;
        }

        $includeRules = collect($this->rules->get('includes', []));
        /** @var ?AllowedIncludePaths $allowedRule */
        $allowedRule = $includeRules->first(fn ($rule) => $rule instanceof AllowedIncludePaths);

        return $allowedRule ? AllowedRule::make($allowedRule)->getAllowed() : Collection::empty();
    }

    /**
     * @return Collection<int, string>|null
     */
    public function fieldSets(): ?Collection
    {
        if ($this->isNotSupported('fields')) {
            return null;
        }

        $fieldsRules = collect($this->rules->get('fields', []));
        /** @var ?AllowedIncludePaths $allowedRule */
        $allowedRule = $fieldsRules->first(fn ($rule) => $rule instanceof AllowedFieldSets);

        return $allowedRule ? AllowedRule::make($allowedRule)->getAllowed() : Collection::empty();
    }

    public function has(string $key): bool
    {
        return $this->rules->has($key);
    }

    public function isRequired(string $key): bool
    {
        return collect($this->rules->get($key))->contains('required');
    }

    public function isNullable(string $key): bool
    {
        return collect($this->rules->get($key))->contains('nullable');
    }

    public function isNotSupported(string $key): bool
    {
        return (bool) collect($this->rules->get($key, []))
            ->first(fn ($rule) => $rule instanceof ParameterNotSupported);
    }

    /**
     * Returns the filters that are allowed for the request based on the validation rules.
     *
     * @return array<int,string>
     */
    protected function allowedFilters(): array
    {
        $filterRules = collect($this->rules->get('filter', []));
        /** @var ?AllowedFilterParameters $allowedRule */
        $allowedRule = $filterRules->first(fn ($rule) => $rule instanceof AllowedFilterParameters);
        $allowedRules = $allowedRule ? AllowedRule::make($allowedRule)->getAllowed() : collect();

        return $allowedRules->all();
    }

    /**
     * Returns the filters that are validated by the request.
     *
     * @return Collection<string,array<string|Rule|ValidationRule>> returns a collection of filter keys and their rules
     * @phpstan-ignore-next-line due to Rule deprecation
     */
    protected function validatedFilters(): Collection
    {
        // @phpstan-ignore-next-line
        return $this->rules->filter(fn ($rules, $key) => Str::startsWith($key, 'filter.'))
            ->mapWithKeys(fn ($rules, $key) => [Str::replace('filter.', '', $key) => Arr::wrap($rules)]);
    }
}
