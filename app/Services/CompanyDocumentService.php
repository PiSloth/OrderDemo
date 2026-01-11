<?php

namespace App\Services;

use App\Models\CompanyDocument;
use App\Models\CompanyDocumentRevision;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CompanyDocumentService
{
    public function createDocument(array $attributes, ?int $userId): CompanyDocument
    {
        return DB::transaction(function () use ($attributes, $userId) {
            $attributes['body'] = $this->sanitizeHtml((string) ($attributes['body'] ?? ''));

            $document = CompanyDocument::create([
                ...$attributes,
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);

            $this->createRevision($document, $userId);

            return $document;
        });
    }

    public function updateDocument(CompanyDocument $document, array $attributes, ?int $userId): CompanyDocument
    {
        return DB::transaction(function () use ($document, $attributes, $userId) {
            $attributes['body'] = $this->sanitizeHtml((string) ($attributes['body'] ?? ''));

            $document->update([
                ...$attributes,
                'updated_by' => $userId,
            ]);

            $this->createRevision($document, $userId);

            return $document;
        });
    }

    public function createRevision(CompanyDocument $document, ?int $userId): CompanyDocumentRevision
    {
        $nextVersion = (int) (CompanyDocumentRevision::query()
            ->where('company_document_id', $document->id)
            ->max('version') ?? 0) + 1;

        return CompanyDocumentRevision::create([
            'company_document_id' => $document->id,
            'version' => $nextVersion,
            'title' => $document->title,
            'body' => $document->body,
            'company_document_type_id' => $document->company_document_type_id,
            'department_id' => $document->department_id,
            'announced_at' => $document->announced_at,
            'edited_by' => $userId,
        ]);
    }

    private function sanitizeHtml(string $html): string
    {
        $html = trim($html);
        if ($html === '') {
            return '';
        }

        // Remove script blocks quickly before DOM parsing.
        $html = (string) Str::of($html)->replaceMatches('/<\s*script\b[^>]*>(.*?)<\s*\/\s*script\s*>/is', '');

        $dom = new \DOMDocument('1.0', 'UTF-8');
        $internalErrors = libxml_use_internal_errors(true);

        // Wrap to reliably get a body element.
        $dom->loadHTML(
            '<!DOCTYPE html><html><head><meta charset="utf-8"></head><body>' . $html . '</body></html>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );

        libxml_clear_errors();
        libxml_use_internal_errors($internalErrors);

        $xpath = new \DOMXPath($dom);

        // Drop high-risk elements.
        foreach (['script', 'iframe', 'object', 'embed'] as $tag) {
            foreach ($dom->getElementsByTagName($tag) as $node) {
                $node->parentNode?->removeChild($node);
            }
        }

        // Remove inline event handlers and javascript: URLs.
        foreach ($xpath->query('//*[@*]') as $node) {
            if (!$node instanceof \DOMElement) {
                continue;
            }

            $toRemove = [];
            foreach ($node->attributes as $attr) {
                $name = strtolower($attr->name);
                $value = trim($attr->value);

                if (str_starts_with($name, 'on')) {
                    $toRemove[] = $attr->name;
                    continue;
                }

                if (in_array($name, ['href', 'src'], true) && str_starts_with(strtolower($value), 'javascript:')) {
                    $toRemove[] = $attr->name;
                    continue;
                }
            }

            foreach ($toRemove as $attrName) {
                $node->removeAttribute($attrName);
            }
        }

        $body = $dom->getElementsByTagName('body')->item(0);
        if (!$body) {
            return '';
        }

        $out = '';
        foreach ($body->childNodes as $child) {
            $out .= $dom->saveHTML($child);
        }

        return trim($out);
    }
}
