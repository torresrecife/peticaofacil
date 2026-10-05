<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\PeticaoModelo;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class NormalizedTextTemplateController extends Controller
{
    public function store(Request $request, PeticaoModelo $modeloNormalizado)
    {
        $data = $this->validateData($request);
        $metadata = (array) ($modeloNormalizado->metadata ?: []);
        $templates = array_values((array) ($metadata['text_templates'] ?? []));
        $templates[] = [
            'id' => (string) Str::uuid(),
            'title' => trim($data['template_title']),
            'content' => $data['template_content'] ?? '',
            'active' => true,
        ];
        $metadata['text_templates'] = $templates;
        $modeloNormalizado->metadata = $metadata;
        $modeloNormalizado->save();

        return redirect()->route('admin.modelos-normalizados.edit', $modeloNormalizado)
            ->with('status', 'Texto predefinido criado.');
    }

    public function update(Request $request, PeticaoModelo $modeloNormalizado, $templateId)
    {
        $data = $this->validateData($request);
        $metadata = (array) ($modeloNormalizado->metadata ?: []);
        $templates = array_values((array) ($metadata['text_templates'] ?? []));
        $found = false;

        foreach ($templates as &$template) {
            if ((string) ($template['id'] ?? '') !== (string) $templateId) {
                continue;
            }
            $template['title'] = trim($data['template_title']);
            $template['content'] = $data['template_content'] ?? '';
            $template['active'] = ($data['template_active'] ?? '1') === '1';
            $found = true;
            break;
        }
        unset($template);

        abort_unless($found, 404);
        $metadata['text_templates'] = $templates;
        $modeloNormalizado->metadata = $metadata;
        $modeloNormalizado->save();

        return redirect()->route('admin.modelos-normalizados.edit', $modeloNormalizado)
            ->with('status', 'Texto predefinido atualizado.');
    }

    protected function validateData(Request $request)
    {
        return $request->validate([
            'template_title' => 'required|string|max:200',
            'template_content' => 'nullable|string',
            'template_active' => 'nullable|in:0,1',
        ]);
    }
}
