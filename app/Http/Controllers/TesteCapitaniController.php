<?php

namespace App\Http\Controllers;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;

class TesteCapitaniController extends Controller
{
    private $api = "https://k1qrd5-tst-protheus.totvscloud.com.br:33389/api/WSDEMANDAS";
    private $usuario = "candidato";
    private $password = "cape123";

    public function index(Request $request)
    {
        $tipo = $request->query('TIPO', null);
        $info = $request->query('INFO', null);

        if (is_null($tipo) || is_null($info)) {
            return view('demandas.index', ['demandas' => [], 'tipo' => '', 'info' => '']);
        }

        $validator = Validator::make($request->all(), [
            'TIPO' => 'required|in:1,2',
            'INFO' => [
                'required',
                function ($attribute, $value, $fail) use ($tipo) {
                    if ($tipo == 1 && !preg_match('/^\d{1,3}$/', $value)) {
                        $fail('O campo Informação deve conter apenas números e ter no máximo 3 caracteres quando o Tipo for 1.');
                    }
                    if ($tipo == 2 && !preg_match('/^[a-zA-ZÀ-ÿ\s]+$/', $value)) {
                        $fail('O campo Informação deve conter apenas letras quando o Tipo for 2.');
                    }
                }
            ]
        ], [
            'TIPO.required' => 'O campo Tipo é obrigatório.',
            'TIPO.in' => 'O campo Tipo deve ser 1 (Código) ou 2 (Descrição).',
            'INFO.required' => 'O campo Informação é obrigatório.',
        ]);

        if ($validator->fails()) {
            return view('demandas.index', compact('tipo', 'info'))->withErrors($validator);
        }

        $response = Http::withBasicAuth($this->usuario, $this->password)
                        ->get($this->api, [
                            'TIPO' => $tipo,
                            'INFO' => $info
                        ]);

        if ($response->failed()) {
            $errorData = $response->json();
            $errorMessage = $errorData['errorMessage'] ?? 'Erro desconhecido ao buscar demandas.';
            return view('demandas.index', compact('tipo', 'info'))->with('errorMessage', $errorMessage);
        }

        $demandas = $response->json();
        return view('demandas.index', compact('demandas', 'tipo', 'info'));
    }


    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'descricao' => 'required|string',
            'descriweb' => 'required|string',
            'tipo' => 'required|string|in:1,2',
            'grupo' => 'required|string',
            'area' => 'required|string',
            'ativo' => 'required|string|in:0,1',
            'atendimento' => 'required|string|in:0,1',
            'prazo' => 'required|integer|min:1'
        ]);

        if ($validator->fails()) {
            return redirect()->route('consulta-demanda.index')->withErrors($validator);
        }

        try {

            $payload = [
                'descricao' => $request->input('descricao'),
                'descriweb' => $request->input('descriweb'),
                'tipo' => $request->input('tipo'),
                'grupo' => $request->input('grupo'),
                'area' => $request->input('area'),
                'ativo' => $request->input('ativo'),
                'atendimento' => $request->input('atendimento'),
                'prazo' => (int) $request->input('prazo')
            ];

            $client = new Client([
                'verify' => false,
                'timeout' => 60,
            ]);

            $response = $client->post($this->api, [
                'auth' => [$this->usuario, $this->password],
                'json' => $payload,
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ]
            ]);

            $data = json_decode($response->getBody(), true);

            return redirect()->route('consulta-demanda.index')->with('successMessage', 'Demanda criada com sucesso!');

        } catch (RequestException $e) {
            $errorMessage = $e->getResponse() ? $e->getResponse()->getBody()->getContents() : $e->getMessage();
            return redirect()->route('consulta-demanda.index')->with('errorMessage', 'Erro ao conectar com a API: ' . $errorMessage);
        }
    }

    public function update(Request $request, $codigo)
    {
        try {
            $payload = [
                'codigo' => $codigo,
                'descricao' => $request->input('descricao'),
                'descriweb' => $request->input('descriweb'),
                'tipo' => $request->input('tipo'),
                'grupo' => $request->input('grupo'),
                'area' => $request->input('area'),
                'ativo' => $request->input('ativo'),
                'atendimento' => $request->input('atendimento'),
                'prazo' => (int) $request->input('prazo'),
            ];

            $client = new Client([
                'verify' => false,
                'timeout' => 60,
            ]);

            $response = $client->put($this->api, [
                'auth' => [$this->usuario, $this->password],
                'json' => $payload,
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ]
            ]);


            $data = json_decode($response->getBody(), true);

            return redirect()->route('consulta-demanda.index')
                ->with('successMessage', 'Demanda atualizada com sucesso!');

        } catch (RequestException $e) {

            $errorMessage = $e->getResponse() ? $e->getResponse()->getBody()->getContents() : $e->getMessage();
            return redirect()->route('consulta-demanda.index')->with('errorMessage', 'Erro ao atualizar a demanda: ' . $errorMessage);
        }
    }

    public function destroy($codigo)
    {
        $demandas = Http::withBasicAuth($this->usuario, $this->password)
                        ->delete($this->api, ['codigo' => $codigo]);

        return redirect()->route('consulta-demanda.index')->with('successMessage', 'Demanda excluída com sucesso!');
    }
}
