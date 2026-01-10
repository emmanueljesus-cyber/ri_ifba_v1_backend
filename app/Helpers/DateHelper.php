<?php

namespace App\Helpers;

use Carbon\Carbon;

/**
 * Helper para manipulação de datas e dias da semana
 * 
 * Centraliza funcionalidades comuns de formatação e conversão de datas
 * usadas em todo o sistema, eliminando duplicação de código.
 */
class DateHelper
{
    /**
     * Converte número do dia da semana para texto em português
     * 
     * @param int $dia Número do dia (0=Domingo, 1=Segunda, ..., 6=Sábado)
     * @return string Nome completo do dia da semana
     */
    public static function getDiaSemanaTexto(int $dia): string
    {
        $dias = [
            0 => 'Domingo',
            1 => 'Segunda-feira',
            2 => 'Terça-feira',
            3 => 'Quarta-feira',
            4 => 'Quinta-feira',
            5 => 'Sexta-feira',
            6 => 'Sábado',
        ];
        
        return $dias[$dia] ?? 'Desconhecido';
    }
    
    /**
     * Converte número do dia da semana para abreviação
     * 
     * @param int $dia Número do dia (0=Domingo, 1=Segunda, ..., 6=Sábado)
     * @return string Abreviação do dia da semana (3 letras)
     */
    public static function getDiaSemanaAbrev(int $dia): string
    {
        $dias = [
            0 => 'Dom',
            1 => 'Seg',
            2 => 'Ter',
            3 => 'Qua',
            4 => 'Qui',
            5 => 'Sex',
            6 => 'Sáb',
        ];
        
        return $dias[$dia] ?? '???';
    }
    
    /**
     * Formata data para padrão brasileiro (dd/mm/yyyy)
     * 
     * @param string|Carbon $data Data a ser formatada
     * @return string Data formatada no padrão brasileiro
     */
    public static function formatarDataBR(string|Carbon $data): string
    {
        return Carbon::parse($data)->format('d/m/Y');
    }
    
    /**
     * Formata data e hora para padrão brasileiro (dd/mm/yyyy hh:mm)
     * 
     * @param string|Carbon $dataHora Data e hora a serem formatados
     * @return string Data e hora formatadas no padrão brasileiro
     */
    public static function formatarDataHoraBR(string|Carbon $dataHora): string
    {
        return Carbon::parse($dataHora)->format('d/m/Y H:i');
    }
    
    /**
     * Retorna array com todos os dias da semana em português
     * 
     * @return array Array associativo [numero => nome]
     */
    public static function getTodosDiasSemana(): array
    {
        return [
            0 => 'Domingo',
            1 => 'Segunda-feira',
            2 => 'Terça-feira',
            3 => 'Quarta-feira',
            4 => 'Quinta-feira',
            5 => 'Sexta-feira',
            6 => 'Sábado',
        ];
    }
    
    /**
     * Retorna o nome do mês em português
     * 
     * @param int $mes Número do mês (1-12)
     * @return string Nome do mês em português
     */
    public static function getMesTexto(int $mes): string
    {
        $meses = [
            1 => 'Janeiro',
            2 => 'Fevereiro',
            3 => 'Março',
            4 => 'Abril',
            5 => 'Maio',
            6 => 'Junho',
            7 => 'Julho',
            8 => 'Agosto',
            9 => 'Setembro',
            10 => 'Outubro',
            11 => 'Novembro',
            12 => 'Dezembro',
        ];
        
        return $meses[$mes] ?? 'Mês Inválido';
    }
}
