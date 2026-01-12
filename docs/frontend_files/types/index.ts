export interface Usuario {
    id: number;
    matricula: string;
    nome: string;
    email: string;
    perfil: "estudante" | "admin";
    bolsista: boolean;
    curso?: string;
    turno?: string;
    preferenciaAlimentar?: "comum" | "ovolactovegetariano";
    fotoUrl?: string;
    diasCadastrados?: number[];
}

export interface Cardapio {
    id: number;
    dataDoCardapio: string;
    pratoPrincipalPtn01: string;
    pratoPrincipalPtn02: string;
    guarnicao?: string;
    acompanhamento01: string;
    acompanhamento02: string;
    salada?: string;
    ovoLactoVegetariano?: string;
    suco?: string;
    sobremesa?: string;
    criadoEm: string;
    atualizadoEm: string;
}

export interface Presenca {
    id: number;
    userId: number;
    refeicaoId: number;
    statusDaPresenca: StatusPresenca;
    validadoEm?: string;
    registradoEm: string;
}

export type StatusPresenca =
    | "confirmado"
    | "validado"
    | "presente"
    | "falta_justificada"
    | "falta_injustificada"
    | "cancelado";

export interface Justificativa {
    id: number;
    userId: number;
    refeicaoId?: number;
    tipo: "antecipada" | "posterior";
    motivo: string;
    anexo?: string;
    status: "pendente" | "aprovada" | "rejeitada";
    enviadoEm: string;
}

export interface FilaExtra {
    id: number;
    userId: number;
    refeicaoId: number;
    statusFilaExtras: "inscrito" | "aprovado" | "rejeitado";
    inscritoEm: string;
    posicao?: number;
}

export interface ApiResponse<T> {
    success: boolean;
    data: T;
    meta?: Record<string, unknown>;
    errors?: Record<string, string[]>;
}
