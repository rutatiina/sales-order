<?php

Route::group(['middleware' => ['web', 'auth', 'tenant', 'service.accounting']], function() {

	Route::prefix('sales-orders')->group(function () {

        //Route::get('summary', 'Rutatiina\SalesOrder\Http\Controllers\SalesOrderController@summary');
        Route::post('export-to-excel', 'Rutatiina\SalesOrder\Http\Controllers\SalesOrderController@exportToExcel');
        Route::post('{id}/approve', 'Rutatiina\SalesOrder\Http\Controllers\SalesOrderController@approve');
        Route::get('{id}/copy', 'Rutatiina\SalesOrder\Http\Controllers\SalesOrderController@copy');

    });

    Route::resource('sales-orders/settings', 'Rutatiina\SalesOrder\Http\Controllers\SalesOrderSettingsController');
    Route::resource('sales-orders', 'Rutatiina\SalesOrder\Http\Controllers\SalesOrderController');

});
