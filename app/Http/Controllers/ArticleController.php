<?php

namespace App\Http\Controllers;

use DB;
use App\Article;
use Illuminate\Http\Request;

class ArticleController extends Controller
{
	public function index()
	{
		$Articles = Article::all();
		return response()->json($Articles);
	}

	public function listing(Request $request)
	{

		//check if status argument is set
		if ($request->has('status')) {

			$unread_item_ids = [];

			//get articles for status equals read and unread
			if ($request->input('status') != 'star') {
				if ($request->has('feed_id')) {
					$Articles = Article::where('feed_id', $request->input('feed_id'))->where('status', $request->input('status'))->orderBy('published', $request->input('sort'))->select('id')->get();
				} elseif ($request->has('category_id')) {
					$Articles = DB::table('categories')->join('feeds', 'categories.id', '=', 'feeds.category_id')->join('articles', 'feeds.id', '=', 'articles.feed_id')->where('categories.id', $request->input('category_id'))->where('articles.status', $request->input('status'))->orderBy('published', $request->input('sort'))->select('articles.id')->get();
				} else {
					$Articles = Article::where('status', $request->input('status'))->orderBy('published', $request->input('sort'))->select('id')->get();
				}
			}

			//get articles for status star, star_ind equals one
			if ($request->input('status') == 'star') {
				if ($request->has('feed_id')) {
					$Articles = Article::where('feed_id', $request->input('feed_id'))->where('star_ind', '1')->orderBy('published', $request->input('sort'))->select('id')->get();
				} else if ($request->has('category_id')) {
					$Articles = DB::table('categories')->join('feeds', 'categories.id', '=', 'feeds.category_id')->join('articles', 'feeds.id', '=', 'articles.feed_id')->where('categories.id', $request->input('category_id'))->where('articles.star_ind', '1')->orderBy('published', $request->input('sort'))->select('articles.id')->get();
				} else {
					$Articles = Article::where('star_ind', '1')->orderBy('published', $request->input('sort'))->select('id')->get();
				}
			}

			//if not empty restructure array with article id's
			if (!empty($Articles)) {
				foreach ($Articles as $Article) {
					array_push($unread_item_ids, $Article->id);
				}
			}

			//return comma separated string with article id's
			return response()->json($unread_item_ids);
		}
	}

	//return article content, based on article_id argument
	public function details(Request $request)
	{
		if ($request->has('article_id')) {
			$articlelist = explode(',', $request->input('article_id'));
			$Articles = DB::table('articles')->join('feeds', 'articles.feed_id', '=', 'feeds.id')->whereIn('articles.id', $articlelist)->orderBy('published', $_GET['sort'])->select('articles.id', 'articles.status', 'articles.star_ind', 'articles.url', 'articles.subject', 'articles.content', 'articles.published', 'articles.feed_id', 'feeds.feed_name', 'feeds.favicon')->get();
			return response()->json($Articles);
		}
	}

	//return array with the count of each status
	public function overview()
	{
		$total  = Article::count();
		$star   = Article::where('star_ind', '1')->count();
		$read   = Article::where('status', 'read')->count();
		$unread = $total - $read;
		return response()->json(compact('total', 'star', 'read', 'unread'));
	}

	public function getArticle($id)
	{
		$Article = Article::find($id);
		return response()->json($Article);
	}

	public function createArticle(Request $request)
	{
		$Article = Article::create($request->all());
		return response()->json($Article);
	}

	public function marktoread($id)
	{
		$Article = Article::find($id);
		if ($Article->status == 'unread') {
			Article::where('id', $id)->update(['status' => 'read']);
			$Article = Article::find($id);
			$Article->category_id = DB::table('articles')->join('feeds', 'articles.feed_id', '=', 'feeds.id')->where('articles.id', $id)->max('feeds.category_id');
			return response()->json($Article);
		}
	}

	public function marktounread($id)
	{
		$Article = Article::find($id);
		if ($Article->status == 'read') {
			Article::where('id', $id)->update(['status' => 'unread']);
			$Article = Article::find($id);
			$Article->category_id = DB::table('articles')->join('feeds', 'articles.feed_id', '=', 'feeds.id')->where('articles.id', $id)->max('feeds.category_id');
			return response()->json($Article);
		}
	}

	public function markwithstar(Request $request, $id)
	{
		if ($request->input('update') == 'mark') {
			Article::where('id', $id)->update(['star_ind' => '1']);
			return response()->json('set to star');
		}

		if ($request->input('update') == 'unmark') {
			Article::where('id', $id)->update(['star_ind' => '0']);
			return response()->json('set to read');
		}
	}

	public function markallasread(Request $request)
	{
		if ($request->input('update') == 'mark-all-as-read') {
			Article::where('status', 'unread')->update(['status' => 'read']);
			return response()->json('marked all as read');
		}
	}

	public function deleteArticle($id)
	{
		$Article = Article::find($id);
		$Article->delete();
		return response()->json('deleted');
	}

	public function updateArticle($id)
	{
		$Article = Article::find($id);
		$Article->name = $request->input('name');
		$Article->save();
		return response()->json($Article);
	}
}
