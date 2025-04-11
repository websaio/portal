import React, { useState } from 'react';
import { Menu, Search, Bell, ChevronDown } from 'lucide-react';
import { useAuth } from '@/hooks/use-auth';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu";
import { Input } from "@/components/ui/input";
import { Avatar, AvatarFallback } from "@/components/ui/avatar";

interface NavbarProps {
  onMenuClick: () => void;
}

export function Navbar({ onMenuClick }: NavbarProps) {
  const { user, logout } = useAuth();
  const [showMobileSearch, setShowMobileSearch] = useState(false);

  const toggleMobileSearch = () => {
    setShowMobileSearch(!showMobileSearch);
  };

  const handleLogout = () => {
    logout();
  };

  return (
    <header className="bg-white border-b border-gray-200 sticky top-0 z-30">
      <div className="flex items-center justify-between px-4 py-3 lg:px-6">
        {/* Mobile menu button */}
        <button 
          className="lg:hidden text-gray-500 hover:text-gray-700 focus:outline-none"
          onClick={onMenuClick}
        >
          <Menu className="h-6 w-6" />
        </button>
        
        {/* Logo */}
        <div className="flex items-center flex-shrink-0 lg:w-64">
          <span className="font-bold text-xl text-primary-600">UIC-IQ</span>
          <span className="ml-2 text-sm font-medium text-gray-500 hidden sm:inline-block">Tuition Management</span>
        </div>
        
        {/* Search bar - Desktop */}
        <div className="hidden lg:flex flex-1 max-w-lg mx-4">
          <div className="relative w-full">
            <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
              <Search className="h-4 w-4 text-gray-400" />
            </div>
            <Input 
              type="text"
              placeholder="Search students, payments..."
              className="pl-10"
            />
          </div>
        </div>
        
        {/* Right nav items */}
        <div className="flex items-center">
          {/* Mobile search toggle */}
          <button 
            className="lg:hidden text-gray-500 hover:text-gray-700 mr-4 focus:outline-none"
            onClick={toggleMobileSearch}
          >
            <Search className="h-5 w-5" />
          </button>
          
          {/* Notifications */}
          <div className="relative mr-4">
            <button className="text-gray-500 hover:text-gray-700 focus:outline-none">
              <Bell className="h-5 w-5" />
              <span className="absolute top-0 right-0 block h-2 w-2 rounded-full bg-red-500"></span>
            </button>
          </div>
          
          {/* User dropdown */}
          {user && (
            <DropdownMenu>
              <DropdownMenuTrigger className="flex items-center focus:outline-none">
                <Avatar className="h-8 w-8 bg-primary-500 text-white">
                  <AvatarFallback>
                    {user.name.split(' ').map(n => n[0]).join('').toUpperCase()}
                  </AvatarFallback>
                </Avatar>
                <span className="ml-2 text-sm font-medium text-gray-700 hidden lg:block">
                  {user.name}
                </span>
                <ChevronDown className="ml-1 h-4 w-4 text-gray-400 hidden lg:block" />
              </DropdownMenuTrigger>
              <DropdownMenuContent align="end">
                <DropdownMenuItem>Your Profile</DropdownMenuItem>
                <DropdownMenuItem>Settings</DropdownMenuItem>
                <DropdownMenuSeparator />
                <DropdownMenuItem onClick={handleLogout}>Sign out</DropdownMenuItem>
              </DropdownMenuContent>
            </DropdownMenu>
          )}
        </div>
      </div>
      
      {/* Mobile search - only shows on mobile */}
      {showMobileSearch && (
        <div className="px-4 pb-3 lg:hidden">
          <div className="relative">
            <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
              <Search className="h-4 w-4 text-gray-400" />
            </div>
            <Input 
              type="text"
              placeholder="Search..."
              className="pl-10"
            />
          </div>
        </div>
      )}
    </header>
  );
}
