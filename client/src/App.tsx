import { Route, Switch } from "wouter";
import { QueryClientProvider } from "@tanstack/react-query";
import { queryClient } from "./lib/queryClient";
import { Toaster } from "@/components/ui/toaster";
import NotFound from "@/pages/not-found";
import Dashboard from "@/pages/dashboard";
import Students from "@/pages/students";
import Payments from "@/pages/payments";
import Receipts from "@/pages/receipts";
import Reports from "@/pages/reports";
import Users from "@/pages/users";
import Settings from "@/pages/settings";
import Login from "@/pages/login";
import { AuthProvider } from "@/contexts/auth-context";
import ProtectedRoute from "@/routes/protected-route";

function App() {
  return (
    <QueryClientProvider client={queryClient}>
      <AuthProvider>
        <Switch>
          <Route path="/login" component={Login} />
          
          <Route path="/dashboard">
            <ProtectedRoute>
              <Dashboard />
            </ProtectedRoute>
          </Route>
          
          <Route path="/students">
            <ProtectedRoute>
              <Students />
            </ProtectedRoute>
          </Route>
          
          <Route path="/payments">
            <ProtectedRoute>
              <Payments />
            </ProtectedRoute>
          </Route>
          
          <Route path="/receipts">
            <ProtectedRoute>
              <Receipts />
            </ProtectedRoute>
          </Route>
          
          <Route path="/reports">
            <ProtectedRoute>
              <Reports />
            </ProtectedRoute>
          </Route>
          
          <Route path="/users">
            <ProtectedRoute isAdminOnly>
              <Users />
            </ProtectedRoute>
          </Route>
          
          <Route path="/settings">
            <ProtectedRoute isAdminOnly>
              <Settings />
            </ProtectedRoute>
          </Route>
          
          <Route path="/">
            <ProtectedRoute>
              <Dashboard />
            </ProtectedRoute>
          </Route>
          
          <Route component={NotFound} />
        </Switch>
        <Toaster />
      </AuthProvider>
    </QueryClientProvider>
  );
}

export default App;
