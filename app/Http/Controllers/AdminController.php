<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Contact;
use App\Models\Tag;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function index(Request $request)
    {
        $query = Contact::query();

        // キーワード入力時のみ検索
        if ($request->filled('keyword')) {
            $keyword = "%{$request->keyword}%";

            // 名前・メールの検索を書く
            $query->where(function ($searchQuery) use ($keyword) {
                $searchQuery->where('first_name', 'like', $keyword)
                    ->orWhere('last_name', 'like', $keyword)
                    ->orWhere('email', 'like', $keyword);
            });
        }

        if ($request->gender != 0) {
            $query->where('gender', $request->gender);
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('date')) {
            $query->whereDate('created_at', $request->date);
        }

        $contacts = $query->paginate(7);
        $categories = Category::all();
        $tags = Tag::all();

        return view('admin.index', compact('contacts', 'categories', 'tags'));
    }

    public function show(Contact $contact)
    {
        return view('admin.show', compact('contact'));
    }

    public function destroy(Contact $contact)
    {
        $contact->delete();

        return redirect('/admin');
    }

    public function exportCsv(Request $request)
    {
        $query = Contact::query();

        if ($request->filled('keyword')) {
            $keyword = "%{$request->keyword}%";

            $query->where(function ($searchQuery) use ($keyword) {
                $searchQuery->where('first_name', 'like', $keyword)
                    ->orWhere('last_name', 'like', $keyword)
                    ->orWhere('email', 'like', $keyword);
            });
        }

        if ($request->gender != 0) {
            $query->where('gender', $request->gender);
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('date')) {
            $query->whereDate('created_at', $request->date);
        }

        $contacts = $query->latest()->get();

        $csv = fopen('php://temp', 'r+');

        fputcsv($csv, [
            'ID',
            '姓',
            '名',
            '性別',
            'メールアドレス',
            '電話番号',
            '住所',
            '建物名',
            'お問い合わせ内容',
            '作成日時',
        ]);

        foreach ($contacts as $contact) {
            fputcsv($csv, [
                $contact->id,
                $contact->last_name,
                $contact->first_name,
                $contact->gender,
                $contact->email,
                $contact->tel,
                $contact->address,
                $contact->building,
                $contact->detail,
                $contact->created_at,
            ]);
        }

        rewind($csv);

        $contents = stream_get_contents($csv);

        fclose($csv);

        return response($contents)
            ->header('Content-Type', 'text/csv; charset=UTF-8')
            ->header(
                'Content-Disposition',
                'attachment; filename="contacts.csv"'
            );
    }
}
