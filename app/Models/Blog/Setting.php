<?php

namespace App\Models\Blog;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use HasFactory;




    public static function getPostOrderingOptions()
    {
      return [
	  ['value' => 2, 'text' => 2],
	  ['value' => 5, 'text' => 5],
	  ['value' => 10, 'text' => 10],
	  ['value' => 15, 'text' => 15],
	  ['value' => 20, 'text' => 20],
	  ['value' => 25, 'text' => 25],
      ];
    }
}
