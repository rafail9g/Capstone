<?php

namespace App\Services;

use Symfony\Component\HttpFoundation\Response;

class PdfDocumentService
{
    /**
     * Buat PDF 1-halaman valid minimal untuk keperluan download/inline preview.
     */
    public function generateDummyPdf(string $title, string $subtitle = 'Dokumen Resmi MBKM Fasilkom UNEJ'): string
    {
        $cleanTitle = str_replace(['(', ')', '\\'], '', $title);
        $cleanSubtitle = str_replace(['(', ')', '\\'], '', $subtitle);

        $stream = "BT /F1 16 Tf 50 780 Td ({$cleanTitle}) Tj ET\nBT /F1 11 Tf 50 755 Td ({$cleanSubtitle}) Tj ET\nBT /F1 10 Tf 50 730 Td (Sistem Informasi MBKM Fakultas Ilmu Komputer Universitas Jember) Tj ET";

        $objs = [
            '<< /Type /Catalog /Pages 2 0 R >>',
            '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
            '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Contents 4 0 R /Resources << /Font << /F1 5 0 R >> >> >>',
            '<< /Length '.strlen($stream)." >>\nstream\n{$stream}\nendstream",
            '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>',
        ];

        $pdf = "%PDF-1.4\n";
        $offsets = [];
        foreach ($objs as $i => $obj) {
            $offsets[] = strlen($pdf);
            $pdf .= ($i + 1)." 0 obj\n{$obj}\nendobj\n";
        }

        $xref = strlen($pdf);
        $pdf .= "xref\n0 ".(count($objs) + 1)."\n0000000000 65535 f \n";
        foreach ($offsets as $off) {
            $pdf .= sprintf("%010d 00000 n \n", $off);
        }
        $pdf .= "trailer\n<< /Size ".(count($objs) + 1)." /Root 1 0 R >>\nstartxref\n{$xref}\n%%EOF";

        return $pdf;
    }

    /**
     * Buat response streaming untuk file PDF.
     */
    public function streamPdfContent(string $content, string $filename, string $mode = 'inline'): Response
    {
        $disposition = $mode === 'download' ? 'attachment' : 'inline';

        return response($content, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => "{$disposition}; filename=\"{$filename}\"",
            'Cache-Control' => 'no-cache, private',
        ]);
    }
}
