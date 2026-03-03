import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ReactiveFormsModule, FormBuilder, FormGroup, Validators } from '@angular/forms';
import { ReservaService } from '../../services/reserva.service';
import { AuthService } from '../../services/auth.service';
import { ActivatedRoute } from '@angular/router';

@Component({
  selector: 'app-reservas',
  standalone: true,
  imports: [CommonModule, ReactiveFormsModule],
  templateUrl: './reservas.component.html',
  styleUrls: ['./reservas.component.css']
})
export class ReservasComponent implements OnInit {
  mostrarFormulario = false;
  reservaForm!: FormGroup;
  canchas: any[] = [];
  reservas: any[] = [];
  reservasActivas: any[] = [];
  reservasUsuario: any[] = [];
  horarios: string[] = [];
  horariosOcupados: string[] = [];
  horariosFin: string[] = [];
  mensaje: string = '';
  erroresBackend: { [key: string]: string[] } | null = null;
  mesActual: number = new Date().getMonth();
  anioActual: number = new Date().getFullYear();
  mesActualNombre: string = '';
  diasDelMes: any[] = [];
  diasSemana: string[] = ['L', 'M', 'M', 'J', 'V', 'S', 'D'];
  reservasDisponibilidad: any[] = [];
  usuariosClientes: any[] = [];
  usuarioActual: any;
  diaSeleccionado: string | null = null;

  esAdmin: boolean = false;
  esMaster: boolean = false;
  esUser: boolean = false;
  enviando = false;
  editandoReserva: any = null;
  
iniciarEdicion(reserva: any) {
  this.editandoReserva = reserva;
  this.mostrarFormulario = true;

  // Buscar los IDs correctos:
  const canchaObj = this.canchas.find(c => c.nombre === reserva.cancha);
  const cancha_id = canchaObj ? canchaObj.id : '';

  const clienteObj = this.usuariosClientes.find(u => u.name === reserva.cliente);
  const cliente_id = clienteObj ? clienteObj.id : '';

  // Ajusta formato horario: solo HH:mm si viene como HH:mm:ss
  const hora_inicio = reserva.hora_inicio && reserva.hora_inicio.length === 8
    ? reserva.hora_inicio.slice(0, 5)
    : reserva.hora_inicio;

  const hora_fin = reserva.hora_fin && reserva.hora_fin.length === 8
    ? reserva.hora_fin.slice(0, 5)
    : reserva.hora_fin;

  // Si tu select de estado usa "activa" en vez de "aprobada"
  const estado = reserva.estado === 'aprobada' ? 'activa' : reserva.estado;

  this.reservaForm.patchValue({
    cliente: reserva.cliente,
    cliente_id: cliente_id,
    cancha_id: cancha_id,
    telefono: reserva.telefono,
    fecha: reserva.fecha,
    hora_inicio: hora_inicio,
    hora_fin: hora_fin,
    estado: estado
  });
 
}


  constructor(private fb: FormBuilder, private reservaService: ReservaService, private authService: AuthService, private route: ActivatedRoute) { }

  ngOnInit(): void {
    this.usuarioActual = this.authService.getUsuario();
    const usuario = this.authService.getUsuario();
    this.esAdmin = usuario?.role === 'administrador';
    this.esMaster = usuario?.role === 'master';

    this.reservaForm = this.fb.group({
      cliente: ['', Validators.required],
      cliente_id: [
        '',
        this.esAdmin || this.esMaster ? [Validators.required] : []
      ],
      cancha_id: ['', Validators.required],
      telefono: ['', Validators.required],
      fecha: ['', Validators.required],
      hora_inicio: [{ value: '', disabled: true }, Validators.required],
      hora_fin: [{ value: '', disabled: true }, Validators.required],
      estado: [this.esAdmin || this.esMaster ? 'aprobada' : 'pendiente', Validators.required]
    });

      this.route.paramMap.subscribe(params => {
    const canchaId = params.get('id');
    if (canchaId) {
      this.reservaForm.patchValue({ cancha_id: canchaId });
    }
  });

    // Solo si NO es admin/master, setea el nombre al FormControl
    if (!this.esAdmin && !this.esMaster && this.usuarioActual) {
      this.reservaForm.get('cliente')?.setValue(this.usuarioActual.name);
      this.reservaForm.get('telefono')?.setValue(this.usuarioActual.telefono);

    }

    if (this.esAdmin || this.esMaster) {
      this.reservaForm.get('cliente_id')?.valueChanges.subscribe(clienteId => {
      const selected = this.usuariosClientes.find(u => u.id == clienteId);
      this.reservaForm.get('cliente')?.setValue(selected?.name || '');
      this.reservaForm.get('telefono')?.setValue(selected?.telefono || '');
    });

    }

    //  Si NO es admin o master se bloquea el campo estado y se fuerza a "pendiente"
    if (!this.esAdmin && !this.esMaster) {
      this.reservaForm.get('estado')?.setValue('pendiente');
      this.reservaForm.get('estado')?.disable();
    } else {
      this.reservaForm.get('estado')?.enable();
    }

    


    // Habilitar hora_inicio cuando hay cancha y fecha
    this.reservaForm.get('cancha_id')?.valueChanges.subscribe(() => {
      const cancha = this.reservaForm.get('cancha_id')?.value;
      const fecha = this.reservaForm.get('fecha')?.value;
      const horaInicio = this.reservaForm.get('hora_inicio');

      if (cancha && fecha) horaInicio?.enable();
      else horaInicio?.disable();
    });

    this.reservaForm.get('fecha')?.valueChanges.subscribe(() => {
      const cancha = this.reservaForm.get('cancha_id')?.value;
      const fecha = this.reservaForm.get('fecha')?.value;
      const horaInicio = this.reservaForm.get('hora_inicio');

      if (cancha && fecha) horaInicio?.enable();
      else horaInicio?.disable();
    });

    // Habilitar hora_fin cuando hay hora_inicio seleccionada
    this.reservaForm.get('hora_inicio')?.valueChanges.subscribe(() => {
      const horaInicio = this.reservaForm.get('hora_inicio')?.value;
      const horaFin = this.reservaForm.get('hora_fin');

      if (horaInicio) {
        horaFin?.enable();
        this.filtrarHorariosFin();
      } else {
        horaFin?.disable();
      }
    });

    //regenerar calendario y cargar disponibilidad
    this.reservaForm.get('cancha_id')?.valueChanges.subscribe(() => {
      this.generarCalendario();
      this.cargarDisponibilidadMes();
    });


    this.cargarCanchas();
    this.generarCalendario();

    if (this.esAdmin || this.esMaster) {
      this.cargarUsuariosClientes();
      this.cargarReservas();
      this.cargarReservasActivas();
    } else if (usuario?.id) {
      this.cargarReservasUsuario(usuario.id);
    }
  }

  // Obtiene primer error del backend
  getPrimerError(key: string): string {
    if (!this.erroresBackend) return '';
    const val = this.erroresBackend[key];
    return Array.isArray(val) && val.length > 0 ? val[0] : '';
  }

  cargarCanchas() {
    this.reservaService.getCanchas().subscribe({
      next: (data) => this.canchas = data,
      error: (err) => console.error('Error al cargar canchas', err)
    });
  }

  cargarReservas() {
    this.reservaService.getReservas().subscribe({
      next: (data) => (this.reservas = data),
      error: (err) => console.error('Error cargando reservas:', err),
    });
  }

  cargarReservasActivas() {
    this.reservaService.getReservasActivas().subscribe({
      next: (data) => { this.reservasActivas = data; },
      error: (err) => console.error('Error al cargar reservas activas', err)
    });
  }

  cargarReservasUsuario(userId: number) {
    this.reservaService.getReservasUsuario(userId).subscribe({
      next: (data) => { this.reservasUsuario = data; },
      error: (err) => console.error('Error al cargar reservas del usuario', err)
    });
  }

  // Cargar horarios disponibles para la fecha seleccionada
  cargarHorarios() {
    const fecha = this.reservaForm.get('fecha')?.value;
    const canchaId = this.reservaForm.get('cancha_id')?.value;
    if (!fecha || !canchaId) return;

    this.reservaService.getHorarios(fecha, canchaId).subscribe({
      next: (data) => {
        this.horarios = data.filter(h => h !== '00:00'); // excluir 00:00 del inicio

        const todosHorarios = [];
        for (let h = 16; h <= 23; h++) todosHorarios.push(`${h.toString().padStart(2, '0')}:00`);
        todosHorarios.push('00:00');

        this.horariosOcupados = todosHorarios.filter(h => !data.includes(h));
        this.filtrarHorariosFin();
      },
      error: (err) => console.error('Error al cargar horarios', err)
    });
  }

  cargarUsuariosClientes() {
    this.reservaService.getUsuariosClientes().subscribe({
      next: (data) => {
        // this.usuariosClientes = data;  // lista todos los usuario 
        this.usuariosClientes = data.filter(u => u.role === 'usuario');
      },
      error: (err) => console.error('Error cargando clientes', err)
    });
  }

  toggleFormulario() {
  this.mostrarFormulario = !this.mostrarFormulario;

  // Si se cierra el formulario (por cancelar o después de guardar)
  if (!this.mostrarFormulario) {
    const canchaSeleccionada = this.reservaForm.get('cancha_id')?.value; // Guardamos la cancha actual

    this.reservaForm.reset();

    // Restauramos los valores base según el tipo de usuario
    if (this.esAdmin || this.esMaster) {
      this.reservaForm.patchValue({
        estado: 'activa',
        cancha_id: canchaSeleccionada   // 🔹 mantenemos la cancha seleccionada
      });
    } else if (this.usuarioActual) {
      this.reservaForm.patchValue({
        cliente: this.usuarioActual.name,
        cliente_id: this.usuarioActual.id,
        telefono: this.usuarioActual.telefono,
        estado: 'pendiente',
        cancha_id: canchaSeleccionada   // 🔹 mantenemos la cancha seleccionada
      });
    }

    // Limpiamos auxiliares
    this.horarios = [];
    this.horariosOcupados = [];
    this.horariosFin = [];
    this.diaSeleccionado = null;
    this.erroresBackend = null;
  }
}

private resetearFormulario() {
  const canchaSeleccionada = this.reservaForm.get('cancha_id')?.value;
  const fechaSeleccionada = this.diaSeleccionado;

  this.reservaForm.reset();


  if (this.esAdmin || this.esMaster) {
    this.reservaForm.patchValue({
      estado: 'activa',
      cancha_id: canchaSeleccionada // ✅ mantenemos la cancha

    });
  } else if (this.usuarioActual) {
    this.reservaForm.patchValue({
      cliente: this.usuarioActual.name,
      cliente_id: this.usuarioActual.id,
      telefono: this.usuarioActual.telefono,
      estado: 'pendiente',
      cancha_id: canchaSeleccionada // ✅ mantenemos la cancha

    });
  }
  this.diaSeleccionado = fechaSeleccionada; // ✅ Mantiene fecha

  this.horarios = [];
  this.horariosOcupados = [];
  this.horariosFin = [];
  this.diaSeleccionado = null;
  this.erroresBackend = null;
}


confirmarReserva() {
  // Asignaciones previas según el rol
  if (this.esAdmin || this.esMaster) {
    const clienteId = this.reservaForm.get('cliente_id')?.value;
    const selected = this.usuariosClientes.find(u => u.id == clienteId);
    const nombre = selected ? (selected.nombre || selected.name) : '';
    this.reservaForm.get('cliente')?.setValue(nombre);
  } else {
    this.reservaForm.get('cliente_id')?.setValue(this.usuarioActual.id);
  }

  // Validación de formulario
  if (this.reservaForm.invalid) return;

  this.enviando = true;
  const payload = { ...this.reservaForm.getRawValue() };

  // Si está en edición
  if (this.editandoReserva) {
    this.reservaService.actualizarReserva(this.editandoReserva.id, payload).subscribe({
      next: (response) => {
        this.enviando = false;
        this.mensaje = (response as any).message || 'Reserva actualizada correctamente';
        // this.toggleFormulario();
        this.resetearFormulario();  // 👈 Aquí en lugar de toggleFormulario()
        this.cargarReservasActivas();
        this.editandoReserva = null;
        setTimeout(() => this.mensaje = '', 4000);
      },
      error: (err) => {
        this.enviando = false;
        this.erroresBackend = err.error?.errors || { general: [err.error?.error || 'Ocurrió un error'] };
      }
    });
    return;
  }

  // Si NO está en edición, crear reserva
  this.reservaService.crearReserva(payload).subscribe({
    next: (response) => {
      this.enviando = false;
      this.mensaje = (response as any).message || 'Reserva creada correctamente';
      // this.toggleFormulario();
       this.resetearFormulario();  // 👈 Aquí en lugar de toggleFormulario()
      if (this.esAdmin || this.esMaster) {
        this.cargarReservasActivas();
      } else {
        this.cargarDisponibilidadMes();
      }
      setTimeout(() => { this.mensaje = ''; }, 8000);
    },
    error: (err) => {
      this.enviando = false;
      if (err.status === 422) {
        this.erroresBackend = err.error.errors;
      } else if (err.status === 409) {
        this.erroresBackend = { horario: ['Ya existe una reserva en ese horario'] };
      } else {
        this.erroresBackend = { general: [err.error?.error || 'Ocurrió un error'] };
      }
    }
  });
}

  filtrarHorariosFin() {
    const inicio = this.reservaForm.get('hora_inicio')?.value;
    let todasOpciones = [...this.horarios];
    if (!todasOpciones.includes('00:00')) todasOpciones.push('00:00');

    if (!inicio) {
      this.horariosFin = todasOpciones.filter(h => !this.horariosOcupados.includes(h));
      return;
    }

    let inicioHora = parseInt(inicio.split(':')[0], 10);
    this.horariosFin = todasOpciones
      .filter(h => {
        let hora = h === '00:00' ? 24 : parseInt(h.split(':')[0], 10);
        return hora > inicioHora && !this.horariosOcupados.includes(h);
      })
      .sort((a, b) => {
        let horaA = a === '00:00' ? 24 : parseInt(a.split(':')[0], 10);
        let horaB = b === '00:00' ? 24 : parseInt(b.split(':')[0], 10);
        return horaA - horaB;
      });
  }

  generarCalendario() {
    this.mesActualNombre = new Date(this.anioActual, this.mesActual).toLocaleString('es-AR', { month: 'long' });

    const primerDiaMes = new Date(this.anioActual, this.mesActual, 1);
    const ultimoDiaMes = new Date(this.anioActual, this.mesActual + 1, 0);

    const dias = [];
    const primerDiaSemana = (primerDiaMes.getDay() + 6) % 7;
    // Agregar huecos al inicio del mes para alinear días
    for (let i = 0; i < primerDiaSemana; i++) {
      dias.push({ numero: null, estado: 'vacío', fecha: null });
    }

    // Agregar días del mes
    for (let d = 1; d <= ultimoDiaMes.getDate(); d++) {
      const fecha = new Date(this.anioActual, this.mesActual, d);
      dias.push({ numero: d, fecha, estado: 'libre' });
    }

    this.diasDelMes = dias;

    // Cargar disponibilidad desde el backend solo si hay cancha seleccionada
    const canchaId = this.reservaForm.get('cancha_id')?.value;
    if (canchaId) {
      this.cargarDisponibilidadMes();
    }
  }

  cambiarMes(direccion: number) {
    this.mesActual += direccion;
    if (this.mesActual < 0) { this.mesActual = 11; this.anioActual--; }
    else if (this.mesActual > 11) { this.mesActual = 0; this.anioActual++; }
    this.generarCalendario();
  }

  cargarDisponibilidadMes() {
    const canchaId = this.reservaForm.get('cancha_id')?.value;
    if (!canchaId)
      return;


    const mes = `${this.anioActual}-${(this.mesActual + 1).toString().padStart(2, '0')}`;

    this.reservaService.getDisponibilidadMes(mes, canchaId).subscribe({
      next: (data) => {

        this.diasDelMes = this.diasDelMes.map(dia => {
          if (!dia.fecha) return dia;

          const fechaISO = dia.fecha.toISOString().split('T')[0];
          const estadoServidor = data[fechaISO];

          if (estadoServidor === 'ocupado' || estadoServidor === 'lleno' || estadoServidor === 'sin_disponibilidad') {
            dia.estado = 'ocupado';
          } else if (estadoServidor === 'parcial' || estadoServidor === 'incompleto') {
            dia.estado = 'parcial';
          } else {
            dia.estado = 'libre';
          }
          return dia;
        });

      },
      error: (err) => console.error('Error cargando disponibilidad', err)
    });
  }


  seleccionarDia(dia: any) {
    // Ignorar días sin fecha o días ocupados
    if (!dia.fecha || dia.estado === 'ocupado' || dia.numero === null) return;
    this.diaSeleccionado = dia.fecha.toISOString().split('T')[0];
    // Actualizar el valor del formulario
    this.reservaForm.patchValue({ fecha: this.diaSeleccionado });
    // Cargar horarios disponibles para la cancha y fecha seleccionada
    this.cargarHorarios();
  }
  
eliminarReserva(reservaId: number) {
  if (!confirm('¿Seguro que quieres eliminar esta reserva?')) return;
  this.reservaService.eliminarReserva(reservaId).subscribe({
    next: () => {
      this.reservasActivas = this.reservasActivas.filter(r => r.id !== reservaId);
      this.mensaje = 'Reserva eliminada correctamente';
      setTimeout(() => this.mensaje = '', 4000);
    },
    error: (err) => {
      this.mensaje = 'Error al eliminar la reserva';
      setTimeout(() => this.mensaje = '', 4000);
    }
  });
}

cancelarEdicion() {
  this.mostrarFormulario = false;
  this.editandoReserva = null;
  this.reservaForm.reset({ estado: this.esAdmin || this.esMaster ? 'aprobada' : 'pendiente' });
}


}
