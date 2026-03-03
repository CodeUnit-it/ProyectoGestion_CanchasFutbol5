// app.routes.ts
import { Routes } from '@angular/router';
import { LandingComponent } from './landing/landing.component';
import { HomeComponent } from './componentes/home/home.component';
import { InicioComponent } from './componentes/inicio/inicio.component';
import { LoginComponent } from './componentes/login/login.component';
import { DashboardComponent } from './componentes/dashboard/dashboard.component';
import { ReservasComponent } from './componentes/reservas/reservas.component';
import { CanchasComponent } from './componentes/canchas/canchas.component';
import { RegisterComponent } from './componentesReutilizables/register/register.component';
import { PerfilComponent } from './componentes/perfil/perfil.component';
import { UsuariosComponent } from './componentes/usuarios/usuarios.component';


export const routes: Routes = [
  { path: '', component: LandingComponent },
  {
    path: 'home',
    component: HomeComponent,
    children: [
      { path: '', redirectTo: 'inicio', pathMatch: 'full' },
      { path: 'inicio', component: InicioComponent },
      { path: 'dashboard', component: DashboardComponent },
      { path: 'reservas', component: ReservasComponent },
      { path: 'canchas', component: CanchasComponent },
      { path: 'usuarios', component:UsuariosComponent},
      { path: 'perfil', component: PerfilComponent},
      { path: 'reservas/:id', component: ReservasComponent }

    ],
  },
  { path: 'register', component: RegisterComponent },
  { path: 'login', component: LoginComponent },
  { path: '**', redirectTo: '', pathMatch: 'full' },
];
