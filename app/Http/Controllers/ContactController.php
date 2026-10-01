<?php

namespace App\Http\Controllers;

use App\Http\Requests\ExportContactRequest;
use App\Http\Requests\StoreContactRequest;
use App\Models\Category;
use App\Models\Contact;
use App\Models\Tag;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    public function index(Request $request)
    {
        $categories = Category::all();
        $tags = Tag::all();

        if ($request->hasAny([
            'first_name',
            'last_name',
            'gender',
            'email',
            'tel',
            'address',
            'building',
            'category_id',
            'tag_ids',
            'detail',
        ])) {

            session()->flashInput($request->all());
        }

        return view('contact.index', compact('categories', 'tags'));

    }

    public function confirm(StoreContactRequest $request)
    {

        $validated = $request->validated();
        $category = Category::find($validated['category_id']);
        $tags = Tag::whereIn('id', $validated['tag_ids'] ?? [])->get();

        return view('contact.confirm', ['validated' => $validated, 'category' => $category, 'tags' => $tags]);
    }

    public function store(StoreContactRequest $request)
    {
        $validated = $request->validated();
        $contact = Contact::create([
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'gender' => $validated['gender'],
            'email' => $validated['email'],
            'tel' => $validated['tel'],
            'address' => $validated['address'],
            'building' => $validated['building'],
            'category_id' => $validated['category_id'],
            'detail' => $validated['detail']]);

        if (! empty($validated['tag_ids'])) {
            $contact->tags()->sync($validated['tag_ids']);
        }

        return redirect('/thanks');

    }

    public function thanks()
    {
        return view('contact.thanks');
    }

    public function export(ExportContactRequest $request)
    {
        $query = Contact::query();

        if ($request->filled('keyword')) {
            // キーワード検索
            $keyword = "%{$request->keyword}%";
            $query->where(function ($searchQuery) use ($keyword) {
                $searchQuery->where('first_name', 'like', $keyword)
                    ->orWhere('last_name', 'like', $keyword)
                    ->orWhere('email', 'like', $keyword);
            });
        }

        if ($request->gender != 0) {
            // 性別検索
            $query->where('gender', $request->gender);
        }

        if ($request->filled('category_id')) {
            // カテゴリ検索
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('date')) {
            // 日付検索
            $query->whereDate('created_at', $request->date);
        }

        $query->orderBy('created_at', 'desc')
            ->orderBy('id', 'desc');

        $contacts = $query->get();

        $handle = fopen('php://temp', 'w');

        fwrite($handle, "\xEF\xBB\xBF");

        fputcsv($handle, [
            'ID',
            '氏名',
            '性別',
            'メール',
            '電話',
            '住所',
            '建物',
            'カテゴリ',
            '内容',
            '作成日時',
        ]);

        $genderLabels = [
            1 => '男性',
            2 => '女性',
            3 => 'その他',
        ];

        foreach ($contacts as $contact) {
            fputcsv($handle, [
                $contact->id,
                $contact->first_name.' '.$contact->last_name,
                $genderLabels[$contact->gender] ?? '',
                $contact->email,
                $contact->tel,
                $contact->address,
                $contact->building,
                $contact->category->content ?? '',
                $contact->detail,
                $contact->created_at,
            ]);
        }

        rewind($handle);

        return response()->streamDownload(function () use ($handle) {
            fpassthru($handle);
        }, 'contacts.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
