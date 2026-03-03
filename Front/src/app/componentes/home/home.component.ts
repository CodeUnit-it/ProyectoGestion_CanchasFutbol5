import { Component } from '@angular/core';
import { Router, RouterModule, RouterOutlet } from '@angular/router';
import { AuthService } from '../../services/auth.service';
import { CommonModule } from '@angular/common';

@Component({
  selector: 'app-home',
  standalone: true,
  imports: [RouterModule, RouterOutlet, CommonModule],
  templateUrl: './home.component.html',
  styleUrls: ['./home.component.css'], // <--- corregido
})
export class HomeComponent {
  isLoggedIn = false;
  isAdmin = false;
  isUser = false;
  isMaster = false;

  constructor(private authService: AuthService, private router: Router) {
    this.authService.loggedIn$.subscribe(
      (status) => (this.isLoggedIn = status)
    );
    this.authService.currentUserRole$.subscribe((role) => {
      this.isAdmin = role === 'administrador';
      this.isUser = role === 'usuario';
      this.isMaster = role === 'master';
    });
  }

  logout(): void {
    this.authService.logout();
    this.router.navigate(['/home']);
  }
}
