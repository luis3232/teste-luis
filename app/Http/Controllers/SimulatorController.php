<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class SimulatorController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('simulador.index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        $numeroProcessos = 0;
        $tiposAlgoritmo = [
            'FIFO',
            'RR',
            'SJF',
            'SJRTF'
        ];
        $tipo_algoritmo = '';

        if($request->has('numero_processos') && $request->numero_processos > 0){
            $numeroProcessos = (int) $request->numero_processos;
        }
        if($request->has('tipo_algoritmo') && in_array($request->tipo_algoritmo, ['FIFO','RR','SJF','SJRTF'])){
            $tipo_algoritmo = $request->tipo_algoritmo;
        }

        return view('simulador.create', [
            'numeroProcessos' => $numeroProcessos,
            'tiposAlgoritmo' => $tiposAlgoritmo,
            'tipo_algoritmo' => $tipo_algoritmo
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $data["numeroProcessos"] = $request->numeroProcessos;
        $data["tipo_algoritmo"] = $request->tipo_algoritmo;
        if($request->has("tempo_quantum")){
            $data["tempo_quantum"] = $request->tempo_quantum;
        }
        for($i = 0; $i < $data["numeroProcessos"]; $i++){
            $data["tempo_ingresso_" . $i] = $request['tempo_ingresso_' . $i];
            $data["tempo_duracao_" . $i] = $request['tempo_duracao_' . $i];
        }

        return redirect()->route('simulador.resultado', $data);
    }

    public function resultado(Request $request)
    {
        $data["numeroProcessos"] = $request->numeroProcessos;
        $data["tipo_algoritmo"] = $request->tipo_algoritmo;
        if($request->has("tempo_quantum")){
            $data["tempo_quantum"] = $request->tempo_quantum;
        }

        $data['processos'] = [];
        if($data["tipo_algoritmo"] == 'SJF'){
            for($i = 0; $i < $data["numeroProcessos"]; $i++){
                $data['processos'][$i]["tempo_duracao"] = $request['tempo_duracao_' . $i];
                $data['processos'][$i]["tempo_ingresso"] = $request['tempo_ingresso_' . $i];
            }
        }else{
            for($i = 0; $i < $data["numeroProcessos"]; $i++){
                $data['processos'][$i]["tempo_ingresso"] = $request['tempo_ingresso_' . $i];
                $data['processos'][$i]["tempo_duracao"] = $request['tempo_duracao_' . $i];
            }
        }

        $processosBySortAsc = $data['processos'];
        // calcula o tempo total de duração
        $tempoTotalDuracao = [];
        foreach($data['processos'] as $key => $item){
            $tempoTotalDuracao[] = $item['tempo_duracao'];
        }
        $tempo_total_duracao = array_sum($tempoTotalDuracao);

        // verifica o menor tempo de ingresso
        $tempoIngresso = [];
        foreach($data['processos'] as $key => $item){
            $tempoIngresso[] = $item['tempo_ingresso'];
        }
        $menorTempoIngresso = 0;
        asort($tempoIngresso);

        $menorTempoIngresso = array_shift($tempoIngresso);

        $diagramaTempo = [];
        $diagramaTempoTeste = [];

        // fifo
        if($request->tipo_algoritmo == 'FIFO'){
            $filaIngresso = collect($processosBySortAsc);
            $filaAptos = [];
            $clock = 0; // tempo de ingresso dos processos na fila de pronto
            $tempoFim = 0;
            $tempoInicio = 0;
            for ($i=0; $i <= $tempo_total_duracao; $i++) { // PAI 
                if($i == $menorTempoIngresso){
                    $filaAptos = $filaIngresso->where('tempo_ingresso', $menorTempoIngresso)->sortBy('tempo_ingresso');
                }else{
                    $filaAptos = $filaIngresso->where('tempo_ingresso', $i)->sortBy('tempo_ingresso');
                }

                if($i == 0){
                    $tempoInicio = 0; // 0
                }
                
                foreach ($filaAptos as $key => $processo) {
                    if($clock == 0){
                        $tempoFim = $processo['tempo_duracao'];
                    }
                    if($clock > 0){
                        $tempoInicio = $tempoFim;
                        $tempoFim += $processo['tempo_duracao'];
                    }
                    $diagramaTempoTeste['t'.$clock] = [
                        'quantidade_td' => $processo['tempo_duracao'],
                        'tempo_ingresso' => $processo['tempo_ingresso'],
                        'tempo_inicio' => $tempoInicio,
                        'tempo_fim' => $tempoFim
                    ];
                    $clock++;
                }
            }
        }

        // RR
        if($request->tipo_algoritmo == 'RR'){
            // $tempoRestante = 0;
            // $tempoAtualQuantum = 0;
            // $fila = [];
            // $tempoCount = 0;
            // $processosFinalFila = [];
            // foreach($processosBySortAsc as $key => $item){

            //     $item['tempo_duracao'] >= $data["tempo_quantum"] 
            //         ? $tempoRestante = $item['tempo_duracao'] - $data["tempo_quantum"]
            //         : $tempoRestante = 0;

            //     $item['tempo_duracao'] >= $data["tempo_quantum"] 
            //         ? $tempoAtualQuantum += $data["tempo_quantum"]
            //         : $tempoAtualQuantum += $item['tempo_duracao'];

            //     if($count == 0){
            //         $tempoInicio = 0;
            //     }

            //     if($count > 0){
            //         if($item['tempo_duracao'] >= $data["tempo_quantum"]){
            //             $tempoInicio +=  $data["tempo_quantum"];
            //         }else{
            //             $tempoInicio +=  $item['tempo_duracao'];
            //         }
            //     }
                
            //     $diagramaTempoTeste[$key] = [
            //         'quantidade_td' => $item['tempo_duracao'],
            //         'tempo_ingresso' => $item['tempo_ingresso'],
            //         'tempo_duracao' => $item['tempo_duracao'],
            //         'tempo_inicio' => $tempoInicio,
            //         'tempo_fim' => $tempoAtualQuantum,
            //         'tempo_restante' => $tempoRestante
            //     ];
                
            //     if($tempoRestante > 0 && $tempoRestante <= $item['tempo_duracao']){
            //         array_push($processosBySortAsc, $diagramaTempoTeste[$key]);
            //     }

            //     $count++;
            // }
            $fimProcessamento = false;
            $filaIngresso = collect($processosBySortAsc);
            $filaAptos = [];
            $clock = 0; // tempo de ingresso dos processos na fila de pronto
            $tempoFim = 0;
            $tempoInicio = 0;
            $tempoRestante = 0;
            $processador = 0;
            $onProcessador = [];
            $offProcessador = [];
            $offProcessador['tempo_restante'] = 0;
            $pula = false;
            for ($i=0; $i <= $tempo_total_duracao; $i++) { 

                if($tempoFim == $tempo_total_duracao)
                    break;
                    
                if($i == $menorTempoIngresso){
                    $array = $filaIngresso->where('tempo_ingresso', $menorTempoIngresso)->sortBy('tempo_ingresso')->toArray();
                    if(count($array) > 0){
                        foreach ($array as $n) {
                            $filaAptos[] = $n;
                        }
                        $pula = false;
                    }else{
                        $pula = true;
                    }
                }else{
                    $array = $filaIngresso->where('tempo_ingresso', $i)->sortBy('tempo_ingresso')->toArray();
                    if(count($array) > 0){
                        foreach ($array as $key => $n) {
                            $filaAptos[] = $n;
                        }
                        $pula = false;
                    }else{
                        $pula = true;
                    }
                }

                // if($pula){
                //     continue;
                // }
                

                if($i == 9){
                    dd($tempoInicio, $tempoFim,  $onProcessador, $offProcessador, $filaAptos, $filaIngresso->where('tempo_ingresso', $i)->sortBy('tempo_ingresso')->toArray());
                }

                $tempoInicio = $tempoFim;

                $onProcessador = $filaAptos[$i];
                unset($filaAptos[$i]);
                if($offProcessador['tempo_restante'] > 0){
                    $filaAptos[] = $offProcessador;
                }

                if(isset($onProcessador['tempo_restante'])){
                    for ($j=0; $j < ($onProcessador['tempo_restante'] >= $data['tempo_quantum'] ? $data['tempo_quantum'] : $onProcessador['tempo_restante']); $j++) { 
                        $tempoFim++;
                    }
                }else{
                    for ($j=0; $j < ($onProcessador['tempo_duracao'] >= $data['tempo_quantum'] ? $data['tempo_quantum'] : $onProcessador['tempo_duracao']); $j++) { 
                        $tempoFim++;
                    }
                }

                if(isset($onProcessador['tempo_restante'])){
                    $onProcessador['tempo_restante'] >= $data["tempo_quantum"] 
                        ? $tempoRestante = $onProcessador['tempo_restante'] - $data["tempo_quantum"]
                        : $tempoRestante = 0;
                }else{
                    $onProcessador['tempo_duracao'] >= $data["tempo_quantum"] 
                        ? $tempoRestante = $onProcessador['tempo_duracao'] - $data["tempo_quantum"]
                        : $tempoRestante = 0;
                }

                if(isset($onProcessador['tempo_restante']) && $onProcessador['tempo_restante'] > 0){
                    $diagramaTempoTeste['t'.$i] = [
                        'quantidade_td' => $onProcessador['tempo_restante'] >= $data['tempo_quantum'] ? $data['tempo_quantum'] : $onProcessador['tempo_restante'],
                        'tempo_ingresso' => $onProcessador['tempo_ingresso'],
                        'tempo_inicio' => $tempoInicio,
                        'tempo_fim' => $tempoFim,
                        'tempo_restante' => $tempoRestante
                    ];
                }else{
                    $diagramaTempoTeste['t'.$i] = [
                        'quantidade_td' => $onProcessador['tempo_duracao'] >= $data['tempo_quantum'] ? $data['tempo_quantum'] : $onProcessador['tempo_duracao'],
                        'tempo_ingresso' => $onProcessador['tempo_ingresso'],
                        'tempo_inicio' => $tempoInicio,
                        'tempo_fim' => $tempoFim,
                        'tempo_restante' => $tempoRestante
                    ];
                }

                $offProcessador = $onProcessador;
                $offProcessador['tempo_restante'] = $tempoRestante;

                /*if($i == 5){
                    dd($tempoInicio, $tempoFim, $diagramaTempoTeste, $onProcessador, $offProcessador, $filaAptos);
                }*/
            }
            //dd($diagramaTempoTeste);
        }

        // SJF
        $collection = collect([]);
        if($request->tipo_algoritmo == 'SJF'){
            asort($processosBySortAsc);
            $collection = collect($processosBySortAsc);
            $filaProntos = [];

            dd($collection->where('tempo_ingresso', '>=', $menorTempoIngresso)->sortBy('tempo_ingresso')->sortBy('tempo_duracao'), $collection);
            foreach($processosBySortAsc as $key => $item){
                if($count == 0 && $item['tempo_duracao'] > 0){
                    $tempoFim = $item['tempo_duracao'];
                }
                if($count > 0 ){
                    $tempoInicio = $tempoFim;
                    $tempoFim += $item['tempo_duracao'];
                }
                $diagramaTempoTeste['t'.$key] = [
                    'quantidade_td' => $item['tempo_duracao'],
                    'tempo_ingresso' => $item['tempo_ingresso'],
                    'tempo_inicio' => $tempoInicio,
                    'tempo_fim' => $tempoFim
                ];
                $count++;
            }
        }

        $data['processosBySortAsc'] = $processosBySortAsc;
        $data['diagramaTempo'] = $diagramaTempo;
        $data['diagramaTempoTeste'] = $diagramaTempoTeste;
        $data['tempo_total_duracao'] = $tempo_total_duracao;

        return view('simulador.resultado', $data);
    }
}
