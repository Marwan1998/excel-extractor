<?php

namespace App\DataTables;

class Buttons
{

   public static function getButtons($noCreate = false, $noExport = false, $noPrint = false, $noReset = false, $noReload = false)
   {
      $cssClassName = 'btn btn-default btn-sm no-corner';

      return [
         $noCreate ? [] :
         [
            'extend' => 'create',
            'className' => $cssClassName,
            'text' => '<i class="fa fa-plus"></i> ' .__('auth.app.create').''
         ],

         $noExport ? [] :
         [
            'extend' => 'export',
            'className' => $cssClassName,
            'text' => '<i class="fa fa-download"></i> ' .__('auth.app.export').''
         ],

         $noPrint ? [] :
         [
            'extend' => 'print',
            'className' => $cssClassName,
            'text' => '<i class="fa fa-print"></i> ' .__('auth.app.print').''
         ],

         $noReset ? [] :
         [
            'extend' => 'reset',
            'className' => $cssClassName,
            'text' => '<i class="fa fa-undo"></i> ' .__('auth.app.reset').''
         ],

         $noReload ? [] :
         [
            'extend' => 'reload',
            'className' => $cssClassName,
            'text' => '<i class="fa fa-refresh"></i> ' .__('auth.app.reload').''
         ],

     ];


   }

}
