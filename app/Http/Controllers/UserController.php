<?php

namespace App\Http\Controllers;

use App\User;
use App\Product;
use App\ShoppingCart;
use Gloudemans\Shoppingcart\Facades\Cart;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class UserController extends Controller
{
    // マイページ画面へ
    public function mypage()
    {
        $user = Auth::user();
        return view('users.mypage', compact('user'));
    }
    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\User  $user
     * @return \Illuminate\Http\Response
     */
    //情報編集画面へ
    public function edit(User $user)
    {
        $user = Auth::user();
        return view('users.edit', compact('user'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\User  $user
     * @return \Illuminate\Http\Response
     */
    // 情報更新機能
    public function update(Request $request, User $user)
    {
        $user = Auth::user();

         $user->name = $request->input('name') ? $request->input('name') : $user->name;
         $user->email = $request->input('email') ? $request->input('email') : $user->email;
         $user->postal_code = $request->input('postal_code') ? $request->input('postal_code') : $user->postal_code;
         $user->address = $request->input('address') ? $request->input('address') : $user->address;
         $user->phone = $request->input('phone') ? $request->input('phone') : $user->phone;
         $user->update();

         return redirect()->route('mypage');
    }

    //お届け先変更画面へ
    public function edit_address()
    {
        $user = Auth::user();

        return view('users.edit_address', compact('user'));
    }

    // パスワード変更画面へ
    public function edit_password()
    {
        return view('users.edit_password');
    }

    // パスワード変更機能
    public function update_password(Request $request)
    {
        $user = Auth::user();

        if ($request->input('password') == $request->input('password_confirmation')) {
            $user->password = bcrypt($request->input('password'));
            $user->update();
        } else {
            return redirect()->route('mypage.edit_password');
        }

        return redirect()->route('mypage');
    }

// お気に入り表示画面へ
    public function favorite()
    {
        $user = Auth::user();

        // お気に入りしたものの情報取得
        $favorites = $user->favorites(Product::class)->get();

        return view('users.favorite', compact('favorites'));
    }

    public function destroy(Request $request)
    {
        $user = Auth::user();

        if ($user->deleted_flag) {
            $user->deleted_flag = false;
        } else {
            $user->deleted_flag = true;
        }

        $user->update();

        Auth::logout();

        return redirect('/');
    }

// 注文履歴ビューと機能
    public function cart_history_index(Request $request)
    {
        $page = $request->page != null ? $request->page : 1;
        $user_id = Auth::user()->id;
        $billings = ShoppingCart::getCurrentUserOrders($user_id);
        $total = count($billings);
        $billings = new LengthAwarePaginator(array_slice($billings, ($page - 1) * 15, 15), $total, 15, $page, array('path' => $request->url()));

        return view('users.cart_history_index', compact('billings', 'total'));
    }
    public function cart_history_show(Request $request)
    {
        $num = $request->num;
        $user_id = Auth::user()->id;

        $cart_info = DB::table('shoppingcart')->where('instance', $user_id)->where('number', $num)->get()->first();

        Cart::instance($user_id)->restore($num);

        $cart_contents = Cart::content();

        Cart::instance($user_id)->store($num);

        Cart::destroy();

        DB::table('shoppingcart')->where('instance', $user_id)
                                 ->where('number', null)
                                 ->update(
                                     [
                                         'code' => $cart_info->code,
                                         'number' => $num,
                                         'price_total' => $cart_info->price_total,
                                         'qty' => $cart_info->qty,
                                         'buy_flag' => $cart_info->buy_flag,
                                         'updated_at' => $cart_info->updated_at
                                     ]
                                 );

        return view('users.cart_history_show', compact('cart_contents', 'cart_info'));
    }

// クレジットカード登録機能
    public function register_card(Request $request)
    {
        $user = Auth::user();

        $pay_jp_secret = env('PAYJP_SECRET_KEY');
        \Payjp\Payjp::setApiKey($pay_jp_secret);

        $card = [];
        $count = 0;

        if ($user->token != "") {
            $result = \Payjp\Customer::retrieve($user->token)->cards->all(array("limit"=>1))->data[0];
            $count = \Payjp\Customer::retrieve($user->token)->cards->all()->count;

            $card = [
                'brand' => $result["brand"],
                'exp_month' => $result["exp_month"],
                'exp_year' => $result["exp_year"],
                'last4' => $result["last4"]
            ];
        }

        return view('users.register_card', compact('card', 'count'));
    }

    public function token(Request $request)
    {
        $pay_jp_secret = env('PAYJP_SECRET_KEY');
        \Payjp\Payjp::setApiKey($pay_jp_secret);

        $user = Auth::user();
        $customer = $user->token;

        if ($customer != "") {
            $cu = \Payjp\Customer::retrieve($customer);
            $delete_card = $cu->cards->retrieve($cu->cards->data[0]["id"]);
            $delete_card->delete();
            $cu->cards->create(array(
                "card" => request('payjp-token')
            ));
        } else {
            $cu = \Payjp\Customer::create(array(
                "card" => request('payjp-token')
            ));
            $user->token = $cu->id;
            $user->update();
        }

        return redirect()->route('mypage');
    }
}
