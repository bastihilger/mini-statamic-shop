<div class="stripeshop-form-fields">
    @foreach ($fields as $field)
        @php($key = $type.'.'.$field['name'])
        <div class="stripeshop-form-field @if ($field['required']) is-required @endif" :class="{ 'has-errors': error('{{ $key }}') }">
            <label class="stripeshop-form-label" for="mini-shop-{{ $type }}-{{ $field['name'] }}">{{ __('mini-shop::shop.'.$field['name']) }}</label>

            @if ($field['name'] === 'country')
                <select class="stripeshop-form-select" id="mini-shop-{{ $type }}-country" x-model="address.{{ $key }}">
                    @foreach (config('mini-shop.countries') as $iso => $country)
                        <option value="{{ $iso }}">{{ $country }}</option>
                    @endforeach
                </select>
            @elseif ($field['name'] === 'salutation')
                <select class="stripeshop-form-select" id="mini-shop-{{ $type }}-salutation" x-model="address.{{ $key }}">
                    @foreach (config('mini-shop.salutations') as $salutation)
                        <option value="{{ $salutation }}">{{ $salutation }}</option>
                    @endforeach
                </select>
            @else
                <input class="stripeshop-form-input" id="mini-shop-{{ $type }}-{{ $field['name'] }}"
                    autocomplete="{{ $field['autocomplete'] ?? 'on' }}"
                    x-model="address.{{ $key }}" @input="clearError('{{ $key }}')">
            @endif

            <div class="stripeshop-form-error" :class="{ 'is-active': error('{{ $key }}') }" x-text="error('{{ $key }}')"></div>
        </div>
    @endforeach
</div>
