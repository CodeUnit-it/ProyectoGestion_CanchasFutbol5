import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { AuthService } from '../../services/auth.service';
import { Router } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { ReservaService } from '../../services/reserva.service';

@Component({
  selector: 'app-perfil',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './perfil.component.html',
  styleUrls: ['./perfil.component.css']
})
export class PerfilComponent implements OnInit {
  usuario: any = null;
  esAdmin = false;
  editandoTelefono = false;
  reservasUsuario: any[] = [];
  mensaje: string = '';

  

  constructor(private authService: AuthService, private reservaService: ReservaService, private router: Router) {}

  ngOnInit(): void {
  this.usuario = this.authService.getUsuario();
  console.log('Usuario en ngOnInit:', this.usuario);
  this.cargarReservasUsuario();


  if (this.usuario && this.usuario.created_at) {
    this.usuario.created_at = new Date(this.usuario.created_at);
    console.log('Fecha created_at convertida:', this.usuario.created_at);
  }

  this.esAdmin = ['administrador', 'master'].includes(this.usuario?.role);
}


  cerrarSesion(): void {
    this.authService.logout();
    this.router.navigate(['/login']);
  }

  editarTelefono(): void {
    this.editandoTelefono = true;
  }
  guardarTelefono(): void {
  if (!this.usuario || !this.usuario.id) return;

  this.authService.actualizarTelefono(this.usuario.id, this.usuario.telefono).subscribe({
    next: (usuarioActualizado) => {
      this.editandoTelefono = false;
      this.usuario = usuarioActualizado;
      localStorage.setItem('usuario', JSON.stringify(this.usuario)); 
      // alert('Teléfono actualizado correctamente');
      
      this.mensaje = 'Teléfono actualizado correctamente';
      setTimeout(() => this.mensaje = '', 8000);
    },
    error: (err) => {
      console.error('Error actualizando teléfono:', err);
      alert('Error al actualizar teléfono');
    }
  });
}
  cancelarEdicion(): void {
    this.editandoTelefono = false;
    this.usuario.telefono = this.authService.getUsuario()?.telefono;
  }

  cargarReservasUsuario() {
  this.reservaService.getReservasUsuario(this.usuario.id)
    .subscribe({
      next: (data) => this.reservasUsuario = data,
      error: (err) => console.error('Error al cargar reservas del usuario', err)
    });
}

}
