@php use Illuminate\Support\Facades\Storage; @endphp
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $questionSet->title }}</title>

    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            color: #111827;
            line-height: 1.5;
        }

        .header {
            text-align: center;
            margin-bottom: 25px;
            border-bottom: 2px solid #111827;
            padding-bottom: 12px;
        }

        .title {
            font-size: 22px;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .meta {
            font-size: 12px;
            color: #4b5563;
        }

        .info {
            margin-bottom: 20px;
        }

        .info table {
            width: 100%;
            border-collapse: collapse;
        }

        .info td {
            padding: 6px;
            border: 1px solid #d1d5db;
        }

        .question {
            margin-bottom: 18px;
            page-break-inside: avoid;
        }

        .question-title {
            font-weight: bold;
            margin-bottom: 8px;
            text-align: justify;
        }

        .options {
            margin-left: 18px;
            margin-bottom: 8px;
            text-align: justify;
        }

        .answer {
            background: #dcfce7;
            border: 1px solid #86efac;
            padding: 8px;
            margin-top: 8px;
            text-align: justify;
        }

        .explanation {
            background: #dbeafe;
            border: 1px solid #93c5fd;
            padding: 8px;
            margin-top: 8px;
            text-align: justify;
        }

        .footer {
            margin-top: 30px;
            font-size: 10px;
            text-align: center;
            color: #6b7280;
        }
    </style>
</head>
<body>

    <div class="header">
        <div class="title">{{ $questionSet->title }}</div>

        <div class="meta">
            {{ $questionSet->subject }} |
            {{ $questionSet->grade }} |
            {{ $questionSet->topic }}
        </div>
    </div>

    <div class="info">
        <table>
            <tr>
                <td><strong>Jenis Soal</strong></td>
                <td>
                    {{ $questionSet->question_type === 'multiple_choice' ? 'Pilihan Ganda' : 'Essay' }}
                </td>

                <td><strong>Kesulitan</strong></td>
                <td>{{ ucfirst($questionSet->difficulty) }}</td>
            </tr>

            <tr>
                <td><strong>Jumlah Soal</strong></td>
                <td>{{ $questionSet->questions->count() }} soal</td>

                <td><strong>Dibuat Pada</strong></td>
                <td>{{ $questionSet->created_at->format('d M Y') }}</td>
            </tr>
        </table>
    </div>

    @foreach($questionSet->questions as $index => $question)
        <div class="question">
            <div class="question-title">
                {{ $index + 1 }}. {!! \App\Services\Document\TextFormatter::toHtml($question->question_text) !!}
            </div>

            @if($question->hasImage())
                @php
                    $imgPath  = Storage::disk('local')->path($question->image_path);
                    $imgData  = file_exists($imgPath) ? base64_encode(file_get_contents($imgPath)) : null;
                    $imgMime  = file_exists($imgPath) ? mime_content_type($imgPath) : 'image/jpeg';
                @endphp
                @if($imgData)
                    <div style="text-align:center; margin: 8px 0;">
                        <img src="data:{{ $imgMime }};base64,{{ $imgData }}"
                             style="max-width:400px; max-height:250px; object-fit:contain;">
                    </div>
                @endif
            @elseif($question->needs_image)
                <div style="border:1px dashed #f59e0b; background:#fffbeb; padding:6px 10px; margin:6px 0; font-size:11px; color:#92400e;">
                    [GAMBAR: {{ $question->image_recommendation ?? 'Sisipkan gambar di sini' }}]
                </div>
            @endif

            @if($questionSet->question_type === 'multiple_choice')
                <div class="options">
                    A. {!! \App\Services\Document\TextFormatter::toHtml($question->option_a) !!} <br>
                    B. {!! \App\Services\Document\TextFormatter::toHtml($question->option_b) !!} <br>
                    C. {!! \App\Services\Document\TextFormatter::toHtml($question->option_c) !!} <br>
                    D. {!! \App\Services\Document\TextFormatter::toHtml($question->option_d) !!}
                </div>
            @endif

            <div class="answer">
                <strong>Jawaban:</strong> {!! \App\Services\Document\TextFormatter::toHtml($question->correct_answer) !!}
            </div>

            @if($question->explanation)
                <div class="explanation">
                    <strong>Pembahasan:</strong> {!! \App\Services\Document\TextFormatter::toHtml($question->explanation) !!}
                </div>
            @endif
        </div>
    @endforeach

    <div class="footer">
        Dicetak dari EduSoal AI - Smart Question Generator
    </div>

</body>
</html>