<?php
namespace App\Services;
use thiagoalessio\TesseractOCR\TesseractOCR;
class OcrKtpService
{
    private const KTP_FIELD_KEYWORDS = [
        'PROVINSI',
        'KABUPATEN',
        'NIK',
        'Nama',
        'Tempat/Tgl Lahir',
        'Jenis Kelamin',
        'Gol. Darah',
        'Alamat',
        'RT/RW',
        'Kel/Desa',
        'Kecamatan',
        'Agama',
        'Status Perkawinan',
        'Pekerjaan',
        'Kewarganegaraan',
        'Berlaku Hingga'
    ];
    public function extractData(string $imagePath): array
    {
        $rawText = (new TesseractOCR($imagePath))->run();
        $cleanedRawTextForExtraction = $this->preprocessOcrTextForExtraction($rawText);
        $formattedTextForDisplay = $this->formatOcrTextForDisplay($rawText);
        $address = $this->formatToTitleCase($this->extractAfter($cleanedRawTextForExtraction, 'Alamat'), true);
        $rtRw = $this->formatToTitleCase($this->extractAfter($cleanedRawTextForExtraction, 'RT/RW'), true);
        $kelDesa = $this->formatToTitleCase($this->extractAfter($cleanedRawTextForExtraction, 'Kel/Desa'), true);
        $kecamatan = $this->formatToTitleCase($this->extractKecamatan($cleanedRawTextForExtraction), true);
        $addressParts = array_filter([$address, $rtRw, $kelDesa, $kecamatan]);
        $fullAddress = implode(' ', $addressParts);
        $ocrResult = [
            'nik' => $this->extractNik($cleanedRawTextForExtraction),
            'full_name' => $this->formatToTitleCase($this->extractAfter($cleanedRawTextForExtraction, 'Nama'), false),
            'birth_place' => $this->formatToTitleCase($this->extractBeforeDelimiter($cleanedRawTextForExtraction, 'Tempat/Tgl Lahir', ['/', ',']), false),
            'birth_date' => $this->extractAfterDelimiter($cleanedRawTextForExtraction, 'Tempat/Tgl Lahir', ['/', ','], 'Gol\. Darah:.*$'),
            'gender' => $this->formatToSentenceCase($this->extractGender($cleanedRawTextForExtraction)),
            'full_address' => $fullAddress,
        ];
        return $ocrResult;
    }
    private function getNextFieldPattern(): string
    {
        $patterns = [];
        foreach (self::KTP_FIELD_KEYWORDS as $keyword) {
            $patterns[] = preg_quote($keyword, '/') . ':\s*';
        }
        return '(?:' . implode('|', $patterns) . '|$)';
    }
    private function preprocessOcrTextForExtraction(string $text): string
    {
        $text = str_replace(["\n", "\r"], ' ', $text);
        $text = preg_replace('/Kecamatan\s*[\-\—:]*\s*[:>]?\s*/i', 'Kecamatan: ', $text);
        $text = preg_replace('/[[:space:]]*[:>][[:space:]]*/', ': ', $text);
        $text = preg_replace('/[|¦]/', 'I', $text);
        $text = preg_replace('/\s+/', ' ', $text);
        return trim($text);
    }
    private function formatOcrTextForDisplay(string $text): string
    {
        $text = preg_replace('/\s+/', ' ', $text);
        $text = preg_replace('/[[:space:]]*[:>][[:space:]]*/', ': ', $text);
        $text = preg_replace('/Kecamatan\s*[\-\—:]*\s*[:>]?\s*/i', 'Kecamatan: ', $text);
        $text = preg_replace('/[|¦]/', 'I', $text);
        foreach (self::KTP_FIELD_KEYWORDS as $keyword) {
            $escapedKeyword = preg_quote($keyword, '/');
            $text = preg_replace("/(?<!\n|\r)({$escapedKeyword}:\s*)/i", "\n$1", $text);
        }
        $text = preg_replace("/\n+/", "\n", $text);
        $text = trim($text);
        return $text;
    }
    private function extractNik(string $text): ?string
    {
        if (preg_match("/NIK:\s*(\d{16})/i", $text, $matches)) {
            return trim($matches[1]);
        }
        return null;
    }
    private function extractGender(string $text): ?string
    {
        if (preg_match("/Jenis Kelamin:\s*(LAKI-LAKI|PEREMPUAN)/i", $text, $matches)) {
            return trim($matches[1]);
        }
        if (preg_match("/(LAKI-LAKI|PEREMPUAN)/i", $text, $matches)) {
            return trim($matches[1]);
        }
        return null;
    }
    private function extractAfter(string $text, string $keyword): ?string
    {
        $escapedKeyword = preg_quote($keyword, '/');
        $nextFieldPattern = $this->getNextFieldPattern();
        if (preg_match("/{$escapedKeyword}:\s*(.+?)(?:\n|\r|\r\n|{$nextFieldPattern})/is", $text, $matches)) {
            $result = trim($matches[1]);
            foreach (self::KTP_FIELD_KEYWORDS as $nextKeyword) {
                $result = preg_replace('/' . preg_quote($nextKeyword, '/') . '.*$/is', '', $result);
            }
            return trim($result);
        }
        return null;
    }
    private function extractBeforeDelimiter(string $text, string $keyword, string|array $delimiters): ?string
    {
        $escapedKeyword = preg_quote($keyword, '/');
        $delimiterPattern = is_array($delimiters)
            ? implode('|', array_map(fn($d) => preg_quote($d, '/'), $delimiters))
            : preg_quote($delimiters, '/');
        if (preg_match("/{$escapedKeyword}:\s*(.+?)\s*(?:{$delimiterPattern})/i", $text, $matches)) {
            return trim($matches[1]);
        }
        return null;
    }
    private function extractAfterDelimiter(string $text, string $keyword, string|array $delimiters, ?string $cleanupPattern = null): ?string
    {
        $escapedKeyword = preg_quote($keyword, '/');
        $delimiterPattern = is_array($delimiters)
            ? implode('|', array_map(fn($d) => preg_quote($d, '/'), $delimiters))
            : preg_quote($delimiters, '/');
        $nextFieldPattern = $this->getNextFieldPattern();
        if (preg_match("/{$escapedKeyword}:\s*.+?\s*(?:{$delimiterPattern})\s*(.+?)(?:\n|\r|\r\n|{$nextFieldPattern})/is", $text, $matches)) {
            $result = trim($matches[1]);
            if ($cleanupPattern) {
                $result = preg_replace("/{$cleanupPattern}/i", '', $result);
            }
            foreach (self::KTP_FIELD_KEYWORDS as $nextKeyword) {
                $result = preg_replace('/' . preg_quote($nextKeyword, '/') . '.*$/is', '', $result);
            }
            return trim($result);
        }
        return null;
    }
    private function extractKecamatan(string $text): ?string
    {
        $nextFieldPattern = $this->getNextFieldPattern();
        if (preg_match("/Kecamatan(?:\s*[\-\—:]*\s*[:>]?)?\s*(.+?)(?:\n|\r|\r\n|{$nextFieldPattern})/is", $text, $matches)) {
            $result = trim($matches[1]);
            foreach (self::KTP_FIELD_KEYWORDS as $nextKeyword) {
                $result = preg_replace('/' . preg_quote($nextKeyword, '/') . '.*$/is', '', $result);
            }
            return trim($result);
        }
        return null;
    }
    private function formatToTitleCase(?string $text, bool $allowSymbols = false): ?string
    {
        if ($text === null || $text === '') {
            return null;
        }
        $cleanedText = preg_replace('/\s+/', ' ', $text);
        if (!$allowSymbols) {
            $cleanedText = preg_replace('/[^a-zA-Z0-9\s-]/', '', $cleanedText);
        }
        return mb_convert_case($cleanedText, MB_CASE_TITLE, 'UTF-8');
    }
    private function formatToSentenceCase(?string $text): ?string
    {
        if ($text === null || $text === '') {
            return null;
        }
        $cleanedText = preg_replace('/\s+/', ' ', $text);
        return ucfirst(mb_strtolower($cleanedText, 'UTF-8'));
    }
}
