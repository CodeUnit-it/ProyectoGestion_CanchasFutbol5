import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import {
  FormBuilder,
  FormGroup,
  Validators,
  ReactiveFormsModule,
  FormsModule,
} from '@angular/forms';
import { Cancha, CanchasService } from '../../services/cancha.service';
import { HttpErrorResponse } from '@angular/common/http';
import { AuthService } from '../../services/auth.service';
import { Route, Router } from '@angular/router';

@Component({
  selector: 'app-canchas',
  standalone: true,
  imports: [CommonModule, FormsModule, ReactiveFormsModule], // 👈 IMPORTANTE
  templateUrl: './canchas.component.html',
  styleUrls: ['./canchas.component.css'],
})
export class CanchasComponent implements OnInit {
  canchas: Cancha[] = [];
  cargando: boolean = true;
  error: string = '';
  isAdmin = false;
  isUser = false;
  isLoggedIn = false;
  isMaster = false;

  formCancha!: FormGroup;
  editando: boolean = false;
  canchaEditandoId: number | null = null;
  mostrarForm: boolean = false;
  mensaje: string = '';


  constructor(
    private canchasService: CanchasService, 
    private fb: FormBuilder,
    private authService: AuthService,
    private router: Router
  ) {
    this.authService.loggedIn$.subscribe(
      (status) => (this.isLoggedIn = status)
    );
    this.authService.currentUserRole$.subscribe((role) => {
      this.isAdmin = role === 'administrador';
      this.isUser = role === 'usuario';
      this.isMaster = role === 'master';
    });
  }

  ngOnInit(): void {
    this.obtenerCanchas();
    this.formCancha = this.fb.group({
      nombre: ['', Validators.required],
      tipo: ['', Validators.required],
      precio_hora: [0, Validators.required],
      cant_jugadores: [0, Validators.required],
      // id: [0, Validators.required]
    });
  }

  obtenerCanchas(): void {
    this.cargando = true;
    this.canchasService.getCanchas().subscribe({
      next: (data: Cancha[]) => {
        this.canchas = data;
        this.cargando = false;
      },
      error: (err: HttpErrorResponse) => {
        this.error = 'Error al cargar las canchas';
        console.error(err);
        this.cargando = false;
      },
    });
  }

  mostrarFormularioCrear(): void {
    this.mostrarForm = true;
    this.editando = false;
    this.canchaEditandoId = null;
    this.formCancha.reset({
      nombre: '',
      tipo: '',
      precio_hora: 0,
      cant_jugadores: 0,
      id: null, // o quita si no es usado
    });
    console.log('Formulario después de reset:', this.formCancha.value);
  }

  mostrarFormularioEditar(cancha: Cancha): void {
    this.mostrarForm = true;
    this.editando = true;
    this.canchaEditandoId = cancha.id || null;
    this.formCancha.setValue({
      nombre: cancha.nombre,
      tipo: cancha.tipo,
      precio_hora: cancha.precio_hora || 0,
      cant_jugadores: cancha.cant_jugadores || 0,
    });
    console.log('Formulario al editar:', this.formCancha.value);
  }

  guardarCancha(): void {
    console.log('Formulario válido:', this.formCancha.valid);
    console.log('Datos del formulario:', this.formCancha.value);
    if (this.formCancha.invalid) return;

    const datos = this.formCancha.value;

    if (this.editando && this.canchaEditandoId !== null) {
  this.canchasService.actualizarCancha(this.canchaEditandoId, datos)
    .subscribe({
      next: (updated: Cancha) => {
        this.obtenerCanchas(); // Recarga todas las canchas desde backend
        this.mostrarForm = false;
         this.mensaje = 'Cancha actualizada correctamente';
        setTimeout(() => this.mensaje = '', 8000);

      },
      error: (err: HttpErrorResponse) => console.error(err),
    });
}
 else {
      this.canchasService.crearCancha(datos).subscribe({
        next: (created: Cancha) => {
          this.canchas.push(created);
          this.mostrarForm = false;
          this.mensaje = 'Cancha creada correctamente';
        setTimeout(() => this.mensaje = '', 8000);

        },
        error: (err: HttpErrorResponse) => console.error(err),
      });
    }
  }

irAReservas(canchaId?: number): void {
  if (!canchaId) {
    console.warn('⚠️ ID de cancha no válido');
    return;
  }
    this.router.navigate(['home', 'reservas', canchaId]);

}

  
eliminarCancha(id: number | undefined): void {
  if (!id) return;
  if (!confirm('¿Seguro que querés eliminar esta cancha?')) return;

  this.canchasService.eliminarCancha(id).subscribe({
    next: () => {
      this.canchas = this.canchas.filter((c) => c.id !== id);
      this.mensaje = 'Cancha eliminada correctamente';
      setTimeout(() => this.mensaje = '', 8000);
    },
    error: (err: HttpErrorResponse) => console.error(err),
  });
}

  cancelar(): void {
    this.mostrarForm = false;
  }
}
