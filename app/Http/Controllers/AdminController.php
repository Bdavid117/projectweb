<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Models\AcademicTerm;
use App\Models\Company;
use App\Models\ExperienceType;
use App\Models\ImportBatch;
use App\Models\RequestStatusHistory;
use App\Models\RequestType;
use App\Models\School;
use App\Models\User;
use App\Support\Input;
use App\Support\Labels;
use App\Support\Navigation;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminController extends Controller
{
    public function users(Request $request): View
    {
        $filters = ['q' => Input::text($request, 'q'), 'rol' => Input::text($request, 'rol')];

        $users = User::query()
            ->when($filters['q'] !== '', function ($query) use ($filters) {
                $like = '%'.addcslashes($filters['q'], '\\%_').'%';
                $query->where(fn ($w) => $w->where('name', 'ilike', $like)->orWhere('email', 'ilike', $like));
            })
            ->when(Role::tryFrom($filters['rol']), fn ($query, Role $role) => $query->where('role', $role->value))
            ->orderBy('name')
            ->paginate(config('program.page_size'))
            ->withQueryString();

        return view('admin.usuarios.index', [
            ...$this->shell($request, 'usuarios'),
            'users' => $users,
            'filters' => $filters,
            'roleOptions' => collect(Role::cases())->mapWithKeys(fn (Role $role) => [$role->value => $role->label()]),
            'totals' => User::query()->selectRaw('role, count(*) as total')->groupBy('role')->pluck('total', 'role'),
        ]);
    }

    public function imports(Request $request): View
    {
        return view('admin.cargas.index', [
            ...$this->shell($request, 'cargas'),
            'batches' => ImportBatch::with('user')->latest('created_at')->latest('id')
                ->paginate(config('program.page_size'))->withQueryString(),
        ]);
    }

    public function catalogs(Request $request): View
    {
        return view('admin.catalogos.index', [
            ...$this->shell($request, 'catalogos'),
            'catalogs' => [
                ['title' => 'Periodos académicos', 'columns' => ['Código', 'Inicio', 'Fin'], 'rows' => AcademicTerm::orderByDesc('fecha_inicio')->get()
                    ->map(fn (AcademicTerm $t) => [$t->codigo, Labels::date($t->fecha_inicio), Labels::date($t->fecha_fin)])->all()],
                ['title' => 'Tipos de solicitud', 'columns' => ['Nombre', 'Descripción'], 'rows' => RequestType::orderBy('nombre')->get()
                    ->map(fn (RequestType $t) => [$t->nombre, $t->descripcion ?? '—'])->all()],
                ['title' => 'Tipos de experiencia', 'columns' => ['Nombre', 'Categoría'], 'rows' => ExperienceType::orderBy('categoria')->orderBy('nombre')->get()
                    ->map(fn (ExperienceType $t) => [$t->nombre, $t->categoria])->all()],
                ['title' => 'Colegios', 'columns' => ['Nombre', 'Carácter', 'Municipio'], 'rows' => School::orderBy('nombre')->get()
                    ->map(fn (School $s) => [$s->nombre, ucfirst($s->caracter), $s->municipio ?? '—'])->all()],
                ['title' => 'Empresas', 'columns' => ['Nombre', 'Sector', 'Ciudad'], 'rows' => Company::orderBy('nombre')->get()
                    ->map(fn (Company $c) => [$c->nombre, $c->sector ?? '—', $c->ciudad ?? '—'])->all()],
            ],
        ]);
    }

    public function audit(Request $request): View
    {
        $entries = RequestStatusHistory::with(['request.type', 'user'])
            ->latest('fecha')->latest('id')
            ->paginate(config('program.page_size'))->withQueryString();

        return view('admin.bitacora.index', [
            ...$this->shell($request, 'bitacora'),
            'entries' => $entries->through(fn (RequestStatusHistory $h) => [
                'when' => $h->fecha->format('d/m/Y H:i'),
                'who' => $h->user->name,
                'what' => 'Solicitud #'.$h->request_id.' · '.($h->request->type->nombre ?? 'sin tipo'),
                'from' => $h->estado_anterior ? Labels::stateLabel($h->estado_anterior) : 'Nueva',
                'to' => Labels::stateLabel($h->estado_nuevo),
                'url' => route('requests.show', $h->request_id),
            ]),
        ]);
    }

    /** @return array{user: array, navGroups: array} */
    private function shell(Request $request, string $active): array
    {
        $user = $request->user();

        return ['user' => $user->viewData(), 'navGroups' => Navigation::forRole($user->role, $active)];
    }
}
