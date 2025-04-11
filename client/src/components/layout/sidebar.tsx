import React from 'react';
import { Link, useLocation } from 'wouter';
import { useAuth } from '@/hooks/use-auth';
import { cn } from '@/lib/utils';
import {
  LayoutDashboard,
  Users,
  CreditCard,
  Receipt,
  FileText,
  Settings,
  Shield,
} from 'lucide-react';

interface SidebarProps {
  isMobile?: boolean;
  onLinkClick?: () => void;
}

export function Sidebar({ isMobile = false, onLinkClick }: SidebarProps) {
  const [location] = useLocation();
  const { user, isAdmin } = useAuth();

  const links = [
    {
      name: 'Dashboard',
      href: '/dashboard',
      icon: <LayoutDashboard className="mr-3 h-5 w-5" />,
      active: location === '/dashboard',
    },
    {
      name: 'Students',
      href: '/students',
      icon: <Users className="mr-3 h-5 w-5" />,
      active: location === '/students',
    },
    {
      name: 'Payments',
      href: '/payments',
      icon: <CreditCard className="mr-3 h-5 w-5" />,
      active: location === '/payments',
    },
    {
      name: 'Receipts',
      href: '/receipts',
      icon: <Receipt className="mr-3 h-5 w-5" />,
      active: location === '/receipts',
    },
    {
      name: 'Reports',
      href: '/reports',
      icon: <FileText className="mr-3 h-5 w-5" />,
      active: location === '/reports',
    }
  ];

  const adminLinks = [
    {
      name: 'Users',
      href: '/users',
      icon: <Shield className="mr-3 h-5 w-5" />,
      active: location === '/users',
    },
    {
      name: 'Settings',
      href: '/settings',
      icon: <Settings className="mr-3 h-5 w-5" />,
      active: location === '/settings',
    }
  ];

  return (
    <div className={cn(
      "flex flex-col",
      isMobile ? "w-full" : "w-64 bg-white border-r border-gray-200"
    )}>
      <div className="flex-1 py-4 space-y-1">
        <nav className="px-2">
          {links.map((link) => (
            <Link 
              key={link.href} 
              href={link.href}
              onClick={onLinkClick}
            >
              <a
                className={cn(
                  "group flex items-center px-2 py-2 text-sm font-medium rounded-md",
                  link.active 
                    ? "bg-primary-50 text-primary-600" 
                    : "text-gray-700 hover:bg-gray-50 hover:text-gray-900"
                )}
              >
                {React.cloneElement(link.icon, { 
                  className: cn(
                    link.icon.props.className,
                    link.active ? "text-primary-500" : "text-gray-400 group-hover:text-gray-500"
                  ) 
                })}
                {link.name}
              </a>
            </Link>
          ))}
          
          {isAdmin && (
            <>
              <div className="mt-8">
                <h3 className="px-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">
                  Administration
                </h3>
                <div className="mt-1 space-y-1">
                  {adminLinks.map((link) => (
                    <Link 
                      key={link.href} 
                      href={link.href}
                      onClick={onLinkClick}
                    >
                      <a
                        className={cn(
                          "group flex items-center px-2 py-2 text-sm font-medium rounded-md",
                          link.active 
                            ? "bg-primary-50 text-primary-600" 
                            : "text-gray-700 hover:bg-gray-50 hover:text-gray-900"
                        )}
                      >
                        {React.cloneElement(link.icon, { 
                          className: cn(
                            link.icon.props.className,
                            link.active ? "text-primary-500" : "text-gray-400 group-hover:text-gray-500"
                          ) 
                        })}
                        {link.name}
                      </a>
                    </Link>
                  ))}
                </div>
              </div>
            </>
          )}
        </nav>
      </div>
      
      {user && (
        <div className="p-4 border-t border-gray-200">
          <div className="flex items-center">
            <div className="flex-shrink-0">
              <div className="h-10 w-10 rounded-full bg-primary-500 flex items-center justify-center text-white font-medium">
                {user.name.split(' ').map(n => n[0]).join('').toUpperCase()}
              </div>
            </div>
            <div className="ml-3">
              <p className="text-sm font-medium text-gray-700">{user.name}</p>
              <p className="text-xs font-medium text-gray-500">{user.role === 'admin' ? 'Administrator' : 'Staff'}</p>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
