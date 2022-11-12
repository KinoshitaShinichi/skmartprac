<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', 'WebController@index');

// ↓カートに入れたあとの表示画面ルート
Route::get('users/carts', 'CartController@index')->name('carts.index');
// ↓カートに入れたあとの機能ルート
Route::post('users/carts', 'CartController@store')->name('carts.store');
// ↓カートに入れたあとの非活性ルート？？練習用だから購入したら削除される機能？
Route::delete('users/carts', 'CartController@destroy')->name('carts.destroy');

// ↓ユーザー登録編集ルート
Route::get('users/mypage', 'UserController@mypage')->name('mypage');
Route::get('users/mypage/edit', 'UserController@edit')->name('mypage.edit');
Route::get('users/mypage/address/edit', 'UserController@edit_address')->name('mypage.edit_address');
Route::put('users/mypage', 'UserController@update')->name('mypage.update');
// ↑ユーザー登録編集ルート

// ↓お気入り表示画面ルート
Route::get('users/mypage/favorite', 'UserController@favorite')->name('mypage.favorite');


// ↓パスワード編集ルート
Route::get('users/mypage/password/edit', 'UserController@edit_password')->name('mypage.edit_password');
Route::put('users/mypage/password', 'UserController@update_password')->name('mypage.update_password');
// ↑パスワード編集ルート

// ↓ユーザー側削除ルート
Route::delete('users/mypage/delete', 'UserController@destroy')->name('mypage.destroy');

// ↓注文履歴ルート
Route::get('users/mypage/cart_history', 'UserController@cart_history_index')->name('mypage.cart_history');
Route::get('users/mypage/cart_history/{num}', 'UserController@cart_history_show')->name('mypage.cart_history_show');

// ↓クレジットカード登録ルート
Route::get('users/mypage/register_card', 'UserController@register_card')->name('mypage.register_card');
Route::post('users/mypage/token', 'UserController@token')->name('mypage.token');

// ↓レビュー送信ルート(画面は商品画面productの方)
Route::post('products/{product}/reviews', 'ReviewController@store');

// ↓お気に入り画面ルート
Route::get('products/{product}/favorite', 'ProductController@favorite')->name('products.favorite');

// ↓
Route::get('products', 'ProductController@index')->name('products.index');
Route::get('products/{product}', 'ProductController@show')->name('products.show');

// ログイン機能へのルート
Auth::routes(['verify' => true]);

// ↓ホーム画面ルート
Route::get('/home', 'HomeController@index')->name('home');

//↓ダッシュボード画面ルート
Route::get('/dashboard', 'Dashboard\Auth\LoginController@showLoginForm');
// Route::get('/dashboard', 'Dashboard\Auth\LoginController@showLoginForm')->middleware('auth:admins');

//????
Route::group(['prefix' => 'dashboard', 'as' => 'dashboard.'], function () {
    Route::get('login', 'Dashboard\Auth\LoginController@showLoginForm')->name('login');
    Route::post('login', 'Dashboard\Auth\LoginController@login')->name('login');
});

Route::group(['prefix' => 'dashboard', 'as' => 'dashboard.','middleware' => 'auth:admins'], function () {
    Route::resource('categories', 'Dashboard\CategoryController')->middleware('auth:admins');
    Route::resource('major_categories', 'Dashboard\MajorCategoryController')->middleware('auth:admins');
    Route::resource('products', 'Dashboard\ProductController')->middleware('auth:admins');
    Route::resource('users', 'Dashboard\UserController')->middleware('auth:admins');
    Route::get('orders', 'Dashboard\OrderController@index')->middleware('auth:admins');
    Route::get('products/import/csv', 'Dashboard\ProductController@import')->name('products.import_csv')->middleware('auth:admins');
    Route::post('products/import/csv', 'Dashboard\ProductController@import_csv')->middleware('auth:admins');
});

if (env('APP_ENV') === 'production') {
    URL::forceScheme('https');
}
