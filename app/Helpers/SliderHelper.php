<?php
namespace App\Helpers;

use App\Models\Slide;
use App\Models\Slider;
use Illuminate\Support\Facades\Request;

/**
 * Class MenuHelper
 * @package App\Helpers
 */
class SliderHelper {


    public static function render($type = 'frontend', $position = 'top')
    {
        $view = $type . '.slider.' . $position;
        $slider = Slider::where('type', $type)->where('position', $position)->first();

        if ($slider) {
            $items = Slide::where('slider_id', $slider->id)->OrderByRaw("SORT ASC")->with('image')->get();

            foreach ($items as $index => $item)
                if($item->image == null)
                    $items->forget($index);

            return view($view, compact('items'));
        }
    }
}
