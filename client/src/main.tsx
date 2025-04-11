import { createRoot } from "react-dom/client";
import App from "./App";
import "./index.css";
import { jwtDecode } from "jwt-decode";

// Preload the JWT library
void jwtDecode;

createRoot(document.getElementById("root")!).render(<App />);
