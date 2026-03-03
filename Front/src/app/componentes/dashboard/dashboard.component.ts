import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashboardService } from '../../services/dashboard.service';
import { RouterModule } from '@angular/router';

@Component({
  selector: 'app-dashboard',
  standalone: true,
  imports: [CommonModule, RouterModule],
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.css'],
})
export class DashboardComponent implements OnInit {
  ocupacion: number | null = null;
  reservasActivas: number = 0;
  reservasActivasList: any[] = [];
  reservasPendientesList: any[] = [];
  reservasCanceladasList: any [] = [];
  usuariosRegistradosList: any[] = [];
  ingresosMensuales: number = 0;
  selectedCard: string | null = null;
  loading: boolean = true;
  error: boolean = false;

  constructor(private dashboardService: DashboardService) {}

  ngOnInit(): void {
    this.dashboardService.getMetrics().subscribe({
      next: (data) => {
          console.log('Ocupación recibida:', data.ocupacion);

        this.ocupacion = data.ocupacion;
        this.reservasActivas = data.reservas_activas;
        this.loading = false;
        this.cargarIngresosMensuales();
      },
      error: (err) => {
        console.error('Error al cargar métricas:', err);
        this.error = true;
        this.loading = false;
      },
    });
  }

  mostrarDetalle(card: string) {
    this.selectedCard = card;

    if (card === 'reservasActivas') {
      this.cargarReservasActivas();
    } else if (card === 'reservasPendientes') {
      this.cargarReservasPendientes();
      } else if (card === 'reservasCanceladas') {
      this.cargarReservasCanceladas();
    } else if (card === 'usuariosRegistrados') {
      this.cargarUsuariosRegistrados();
    } else if (card === 'ingresosMensuales') {
      this.cargarIngresosMensuales();
       } else if (card === 'ocupacion') {  
    this.cargarOcupacionActual();
    }
    
    
  }

  cargarReservasActivas() {
    this.dashboardService.getReservasActivas().subscribe({
      next: (data) => {
        console.log('Reservas activas actualizadas:', data);
        this.reservasActivasList = data;
      },
      error: (err) => {
        console.error('Error al cargar reservas activas:', err);
      },
    });
  }
  
  cargarReservasPendientes() {
    this.dashboardService.getReservasPendientes().subscribe({
      next: (data) => {
        this.reservasPendientesList = data;
      },
      error: (err) => {
        console.error('Error al cargar reservas pendientes:', err);
      },
    });
  }

  cargarReservasCanceladas() {
  this.dashboardService.getReservasCanceladas().subscribe({
    next: (data) => {
      this.reservasCanceladasList = data; 
    },
    error: (err) => {
      console.error('Error al cargar reservas canceladas:', err);
    },
  });
}

  cargarUsuariosRegistrados() {
    this.dashboardService.getUsuariosRegistrados().subscribe({
      next: (data) => {
        this.usuariosRegistradosList = data;
      },
      error: (err) => {
        console.error('Error al cargar usuarios registrados:', err);
      },
    });
  }
  aprobarReserva(id: number) {
    this.dashboardService.actualizarEstadoReserva(id, 'aprobada').subscribe({
      next: () => {
        this.cargarReservasPendientes();
        this.cargarReservasActivas();
        this.selectedCard = 'reservasActivas';
      },
      error: (err) => {
        console.error('Error al aprobar reserva:', err);
      },
    });
  }

  rechazarReserva(id: number) {
    this.dashboardService.actualizarEstadoReserva(id, 'cancelada').subscribe({
      next: () => {
        this.cargarReservasPendientes();
        this.cargarReservasActivas();
      },
      error: (err) => {
        console.error('Error al rechazar reserva:', err);
      },
    });
  }
  cargarIngresosMensuales() {
    this.dashboardService.getIngresosMensuales().subscribe({
      next: (data) => {
        this.ingresosMensuales = data.ingresos;
      },
      error: (err) => {
        console.error('Error al cargar ingresos mensuales:', err);
        this.ingresosMensuales = 0;
      },
    });
  }

 cargarOcupacionActual() {
  this.dashboardService.getMetrics().subscribe({
    next: (data) => {
      this.ocupacion = data.ocupacion;
      this.reservasActivas = data.reservas_activas; 
    },
    error: (err) => {
      this.ocupacion = 0;
    }
  });
}


}
