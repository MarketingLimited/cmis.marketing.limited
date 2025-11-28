<?php

namespace App\View\Components;

use Illuminate\View\Component;

class JsTranslations extends Component
{
    /**
     * Get translations for JavaScript
     *
     * @param string|array|null $keys Specific translation keys or null for all
     * @return array
     */
    public function getTranslations($keys = null)
    {
        // If no keys specified, load all JavaScript translations
        if ($keys === null) {
            return [
                'javascript' => __('javascript'),
                'messages' => __('messages'),
                'common' => __('common'),
            ];
        }

        // If array of keys, fetch each
        if (is_array($keys)) {
            $translations = [];
            foreach ($keys as $key) {
                $translations[$key] = __($key);
            }
            return $translations;
        }

        // Single key
        return [$keys => __($keys)];
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        return view('components.js-translations');
    }
}
