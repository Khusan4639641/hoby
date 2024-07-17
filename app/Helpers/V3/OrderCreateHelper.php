<?php

namespace App\Helpers\V3;

use App\Enums\CategoriesEnum;
use App\Helpers\CategoryHelper;

class OrderCreateHelper
{
  public static function is_available_buying_smartphones($products, $phones_counts = 0): bool
  {
    $phone_categories = CategoryHelper::getPhoneCategoryIDs();
    foreach ($products as $productItem) {
      if (in_array($productItem['category'], $phone_categories)) {
        $phones_counts += $productItem['amount'];
      }
    }

    if ($phones_counts > 2) {
      return false;
    }
    return true;
  }

  public static function is_exists_mobile_categories(array $categories): bool
  {
    $phone_categories = CategoryHelper::getPhoneCategoryIDs();
    foreach ($categories as $productItem) {
      if (in_array($productItem['category'], $phone_categories)) {
        return true;
      }
    }
    return false;
  }

}
