<?php

namespace App\Http\Controllers;

use App\Product;
use App\Category;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $sort_query = [];
        $sorted = "";
        if ($request->sort !== null) {
            $slices = explode(' ', $request->sort);
            $sort_query[$slices[0]] = $slices[1];
            $sorted = $request->sort;
        }

        if ($request->category !== null) {
            $products = Product::where('category_id', $request->category)->sortable($sort_query)->paginate(15);
            $total_count = Product::where('category_id', $request->category)->count();
            $category = Category::find($request->category);
        } else {
            $products = Product::sortable($sort_query)->paginate(15);
            $total_count = "";
            $category = null;
        }

        //----------------↓検索機能実装------------------------
        // 検索フォームで入力された値を取得する
        $search = $request->input('search');
        // もし検索フォームにキーワードが入力されたら
        if ($search) {
            // 全角スペースを半角に変換
            $spaceConversion = mb_convert_kana($search, 's');
            // 単語を半角スペースで区切り、配列にする（例："山田 翔" → ["山田", "翔"]）
            $wordArraySearched = preg_split('/[\s,]+/', $spaceConversion, -1, PREG_SPLIT_NO_EMPTY);
            // 単語をループで回し、商品名と部分一致するものがあれば、$products として保持される
            //正規表現は/  /で囲う。
            // \sはスペース \tタブ
            foreach($wordArraySearched as $value) {
                $products = Product::where('name', 'like', '%'.$value.'%')->sortable($sort_query)->paginate(15);
            }
        }
        //-------------------↑検索機能終了-----------------------

        $sort = [
            '並び替え' => '',
            '価格の安い順' => 'price asc',
            '価格の高い順' => 'price desc',
            '出品の古い順' => 'updated_at asc',
            '出品の新しい順' => 'updated_at desc'
        ];

        $categories = Category::all();
        $major_category_names = Category::pluck('major_category_name')->unique();
        return view('products.index', compact('products','search', 'category', 'categories', 'major_category_names', 'total_count', 'sort', 'sorted'));
    }


    public function favorite(Product $product)
     {
         $user = Auth::user();

         if ($user->hasFavorited($product)) {
             $user->unfavorite($product);
         } else {
             $user->favorite($product);
         }

         return redirect()->route('products.show', $product);
     }

    /**
     * Display the specified resource.
     *
     * @param  \App\Product  $product
     * @return \Illuminate\Http\Response
     */
    public function show(Product $product)
    {
        $reviews = $product->reviews()->get();

         return view('products.show', compact('product', 'reviews'));
    }


}
