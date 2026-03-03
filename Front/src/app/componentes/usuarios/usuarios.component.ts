import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashboardService } from '../../services/dashboard.service';
import { AuthService } from '../../services/auth.service';
import { Router } from '@angular/router';
import { FormBuilder, FormGroup, Validators, ReactiveFormsModule } from '@angular/forms';

@Component({
  selector: 'app-usuarios',
  standalone: true,
  imports: [CommonModule, ReactiveFormsModule],
  templateUrl: './usuarios.component.html',
  styleUrls: ['./usuarios.component.css']
})
export class UsuariosComponent implements OnInit {
  usuarios: any[] = [];
  cargando = true;
  error: string | null = null;
  usuario: any = null;
  esAdmin = false;
  mensaje: string = '';
  usuarioForm!: FormGroup;
  usuarioEditando: any = null;

  constructor(
    private authService: AuthService,
    private router: Router,
    private dashboard: DashboardService,
    private fb: FormBuilder  // Inyectar FormBuilder correctamente
  ) {}

  ngOnInit(): void {
    // Inicializar formulario reactivo con validaciones
    this.usuarioForm = this.fb.group({
      name: ['', [Validators.required, Validators.maxLength(255)]],
      email: ['', [Validators.required, Validators.email, Validators.maxLength(255)]],
      telefono: ['', [Validators.maxLength(20)]],
      role: ['', Validators.required],
      password: ['']  // Opcional para cambiar contraseña
    });

    // Obtener usuario autenticado primero
    this.usuario = this.authService.getUsuario();

    // Si usuario existe, cargar datos al formulario
    if (this.usuario) {
      this.usuarioForm.patchValue({
        name: this.usuario.name,
        email: this.usuario.email,
        telefono: this.usuario.telefono,
        role: this.usuario.role
      });
    }

    // Validar si es admin o master para mostrar acceso
    this.esAdmin = ['administrador', 'master'].includes(this.usuario?.role);

    if (this.esAdmin) {
      this.cargarUsuarios();
    } else {
      this.cargando = false;
      this.error = 'No tiene permisos para ver esta sección.';
    }
  }

  cargarUsuarios(): void {
    this.cargando = true;
    this.dashboard.getUsuariosRegistrados().subscribe({
      next: (data) => {
        this.usuarios = data;
        this.cargando = false;
      },
      error: (err) => {
        console.error('Error al obtener usuarios:', err);
        this.error = 'No se pudieron cargar los usuarios registrados.';
        this.cargando = false;
      },
    });
  }

  cerrarSesion(): void {
    this.authService.logout();
    this.router.navigate(['/login']);
  }

  borrarUsuario(id: number): void {
    const usuarioAEliminar = this.usuarios.find(u => u.id === id);

    if (!usuarioAEliminar) return;

    const rolUsuarioActual = this.usuario?.role;
    const rolUsuarioAEliminar = usuarioAEliminar.role;

    // Reglas:
    // master puede eliminar admin o usuario
    // admin puede eliminar solo usuario
    // usuario no puede eliminar nadie
    if (
      (rolUsuarioActual === 'administrador' && rolUsuarioAEliminar !== 'usuario') ||
      (rolUsuarioActual === 'usuario')
    ) {
      alert('No tienes permisos para eliminar a este usuario.');
      return;
    }

    if (!confirm('¿Seguro que deseas eliminar este usuario? Esta acción no se puede deshacer.')) {
      return;
    }

    this.dashboard.borrarUsuario(id).subscribe({
      next: (res) => {
        this.usuarios = this.usuarios.filter(u => u.id !== id);
        this.mensaje = 'Usuario eliminado correctamente';
        setTimeout(() => this.mensaje = '', 4000);
      },
      error: (err) => {
        alert('No se pudo eliminar el usuario.');
      }
    });
  }

  editarUsuario(usuario: any): void {
    this.usuarioEditando = usuario;
    this.usuarioForm.patchValue({
      name: usuario.name,
      email: usuario.email,
      telefono: usuario.telefono || '',
      role: usuario.role,
      password: ''
    });
  }

  guardarCambios(): void {
    if (this.usuarioForm.invalid || !this.usuarioEditando) return;

    const datos = {...this.usuarioForm.value};
    // No enviar password vacío si no se modificó
    if(!datos.password) delete datos.password;

    this.dashboard.actualizarUsuario(this.usuarioEditando.id, datos).subscribe({
      next: (res: any) => {
        this.mensaje = res.message || 'Usuario actualizado correctamente';
        // Actualizar lista localmente
        const index = this.usuarios.findIndex(u => u.id === this.usuarioEditando.id);
        if (index !== -1) this.usuarios[index] = {...this.usuarioEditando, ...datos};
        this.usuarioEditando = null;
        this.usuarioForm.reset();
        setTimeout(() => this.mensaje = '', 4000);
      },
      error: (err) => {
        alert('Error al actualizar usuario');
      }
    });
  }
}
