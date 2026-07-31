<?php

namespace App\Http\Controllers;

use App\PeticaoModelo;
use App\Services\OpenAIResponsesClient;
use App\Services\PeticaoAssistantService;
use App\Services\PeticaoAssistantStateService;
use Illuminate\Http\Request;

class PeticaoAssistantController extends Controller
{
    public function index(PeticaoAssistantStateService $stateService)
    {
        return view('peticao.assistant', [
            'assistantState' => $stateService->current(),
            'openAiEnabled' => app(OpenAIResponsesClient::class)->isEnabled(),
        ]);
    }

    public function message(Request $request, PeticaoAssistantService $assistant, PeticaoAssistantStateService $stateService)
    {
        $data = $request->validate([
            'message' => 'required|string|max:4000',
        ]);

        $state = $assistant->processMessage($stateService->current(), $data['message']);
        $stateService->store($state);

        return redirect()->route('peticoes.assistente.index');
    }

    public function answerCurrentField(Request $request, PeticaoAssistantService $assistant, PeticaoAssistantStateService $stateService)
    {
        $data = $request->validate([
            'field_value' => 'required|string|max:4000',
        ]);

        $state = $assistant->answerCurrentField($stateService->current(), $data['field_value']);
        $stateService->store($state);

        return redirect()->route('peticoes.assistente.index');
    }

    public function reset(PeticaoAssistantStateService $stateService)
    {
        $stateService->reset();

        return redirect()->route('peticoes.assistente.index')->with('status', 'Assistente reiniciado.');
    }

    public function selectModel(PeticaoModelo $modeloNormalizado, PeticaoAssistantService $assistant, PeticaoAssistantStateService $stateService)
    {
        $state = $assistant->selectModel($stateService->current(), $modeloNormalizado);
        $stateService->store($state);

        return redirect()->route('peticoes.assistente.index');
    }
}
