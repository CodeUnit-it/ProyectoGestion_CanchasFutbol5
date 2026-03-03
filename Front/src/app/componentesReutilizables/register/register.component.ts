import { Component, OnInit } from '@angular/core';
import { FormBuilder, FormGroup, Validators, ReactiveFormsModule } from '@angular/forms';
import { CommonModule } from '@angular/common';
import { Router, RouterLink } from '@angular/router';
import { HttpClient } from '@angular/common/http';
import { AuthService } from '../../services/auth.service';

@Component({
  selector: 'app-register',
  standalone: true,
  imports: [CommonModule, ReactiveFormsModule, RouterLink],
  templateUrl: './register.component.html',
  styleUrls: ['./register.component.css']
})
export class RegisterComponent implements OnInit {
  registerForm!: FormGroup;
  errorMessage: string = '';
  cargando: boolean = false;
  isMaster = false;

  constructor(
    private fb: FormBuilder,
    private http: HttpClient,
    private router: Router,
    private authService: AuthService
  ) {}

  ngOnInit(): void {
  this.registerForm = this.fb.group({
    name: ['', Validators.required],
    email: ['', [Validators.required, Validators.email]],
    telefono: ['', Validators.required],
    password: ['', [Validators.required, Validators.minLength(6)]],
    password_confirmation: ['', [Validators.required, Validators.minLength(6)]],
    role: ['usuario', Validators.required]
  });

  // Verificar rol del usuario logueado
  const rolActual = localStorage.getItem('user_role');
  if (rolActual === 'master') {
    this.isMaster = true;
  }
}


  onSubmit(): void {
    if (this.registerForm.invalid) return;

    this.cargando = true;
    this.errorMessage = '';
    const datos = this.registerForm.value;

    // Registro en backend
    this.http.post('http://localhost:8000/api/register', datos).subscribe({
      next: (res: any) => {
        // Login automático después del registro
        this.authService.login({ email: datos.email, password: datos.password }).subscribe({
          next: () => {
            
            // Redirigir según rol
            if (datos.role === 'administrador') {
              this.router.navigate(['/home/dashboard']);
            } else {
              this.router.navigate(['/home/inicio']);
            }
            this.cargando = false;
          },
          error: (err) => {
            this.errorMessage = 'Error al iniciar sesión';
            this.cargando = false;
          }
        });
      },
      error: (err) => {
        if (err.status === 422 && err.error?.errors) {
          this.errorMessage = Object.values(err.error.errors).flat().join(' ');
        } else {
          this.errorMessage = 'Error al registrar el usuario';
        }
        this.cargando = false;
      }
    });
  }
}
