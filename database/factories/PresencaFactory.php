<?php

namespace Database\Factories;

use App\Models\Presenca;
use App\Models\User;
use App\Models\Refeicao;
use App\Enums\StatusPresenca;
use Illuminate\Database\Eloquent\Factories\Factory;

class PresencaFactory extends Factory
{
    protected $model = Presenca::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'refeicao_id' => Refeicao::factory(),
            'status_da_presenca' => $this->faker->randomElement([
                StatusPresenca::CONFIRMADO,
                StatusPresenca::VALIDADO,
                StatusPresenca::FALTA_JUSTIFICADA,
                StatusPresenca::FALTA_INJUSTIFICADA,
            ]),
            'registrado_em' => now()->subMinutes($this->faker->numberBetween(10, 120)),
            'validado_em' => null,
            'validado_por' => null,
        ];
    }

    /**
     * Presença confirmada (aguardando validação)
     */
    public function confirmada(): static
    {
        return $this->state(fn (array $attributes) => [
            'status_da_presenca' => StatusPresenca::CONFIRMADO,
            'validado_em' => null,
            'validado_por' => null,
        ]);
    }

    /**
     * Presença já validada
     */
    public function validada(): static
    {
        return $this->state(fn (array $attributes) => [
            'status_da_presenca' => StatusPresenca::VALIDADO,
            'validado_em' => now()->subMinutes($this->faker->numberBetween(1, 60)),
            'validado_por' => User::factory()->admin(),
        ]);
    }

    /**
     * Falta justificada
     */
    public function faltaJustificada(): static
    {
        return $this->state(fn (array $attributes) => [
            'status_da_presenca' => StatusPresenca::FALTA_JUSTIFICADA,
            'validado_em' => null,
            'validado_por' => null,
        ]);
    }

    /**
     * Falta injustificada
     */
    public function faltaInjustificada(): static
    {
        return $this->state(fn (array $attributes) => [
            'status_da_presenca' => StatusPresenca::FALTA_INJUSTIFICADA,
            'validado_em' => null,
            'validado_por' => null,
        ]);
    }

    /**
     * Presença cancelada
     */
    public function cancelada(): static
    {
        return $this->state(fn (array $attributes) => [
            'status_da_presenca' => StatusPresenca::CANCELADO,
            'validado_em' => null,
            'validado_por' => null,
        ]);
    }
}

