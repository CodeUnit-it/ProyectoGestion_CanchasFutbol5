import { Component } from '@angular/core';
import { FormBuilder, FormGroup, ReactiveFormsModule, Validators } from '@angular/forms';
import { AuthService } from '../../services/auth.service';
import { CommonModule } from '@angular/common';
import { Router, RouterLink } from '@angular/router';   // 👈 Importar Router

@Component({
  selector: 'app-login',
  templateUrl: './login.component.html',
  standalone: true,
  imports:[ReactiveFormsModule,CommonModule, RouterLink],
  styleUrls: ['./login.component.css']
})
export class LoginComponent {
  loginForm: FormGroup;
  errorMessage: string = '';
  enviando = false;


  constructor(
    private fb: FormBuilder,
    private authService: AuthService,
    private router: Router   // 👈 Inyectar Router
  ) {
    this.loginForm = this.fb.group({
      email: ['', [Validators.required, Validators.email]],
      password: ['', [Validators.required, Validators.minLength(6)]]
    });
  }

  onSubmit(): void {
    if (this.loginForm.invalid) return;

    this.enviando = true; 


    this.authService.login(this.loginForm.value).subscribe({
      next: (res) => {
        this.enviando = false;
        if (res.access_token) {
          this.authService.saveToken(res.access_token);
          // ✅ Redirigir al home
          this.router.navigate(['/home']);
        }
      },
      error: (err) => {
        this.enviando = false;
        if (err?.error?.message) {
          this.errorMessage = err.error.message;
        } else if (err?.status === 0) {
          this.errorMessage = 'No se pudo conectar con el servidor';
        } else {
          this.errorMessage = 'Error en el login';
        }
      }
    });
  }
}
