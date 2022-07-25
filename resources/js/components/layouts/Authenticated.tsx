import React, { useState } from 'react'
import ApplicationLogo from '@/components/ApplicationLogo'
import Dropdown from '@/components/Dropdown'
import NavLink from '@/components/NavLink'
import ResponsiveNavLink from '@/components/ResponsiveNavLink'
import { Link } from '@inertiajs/inertia-react'
import { DefaultProps } from '@/api/types/default'
import SessionExpiredModal from '@/components/SessionExpiredModal'
import { Menu } from '@mantine/core'
import { ChevronDownIcon } from '@heroicons/react/solid'
import { Inertia } from '@inertiajs/inertia'

interface Props extends DefaultProps {
  children?: React.ReactNode
  header?: React.ReactNode
  secondaryHeader?: React.ReactNode
}

export default function Authenticated({
  auth,
  header,
  secondaryHeader,
  children,
}: Props) {
  const [showingNavigationDropdown, setShowingNavigationDropdown] =
    useState(false)

  const logout = () => {
    Inertia.post(route('logout'))
  }

  const routes = {
    admin: [
      { name: 'Dashboard', route: 'admin.dashboard' },
      { name: 'Nodes', route: 'admin.nodes.index' },
      { name: 'Servers', route: 'admin.servers.index' },
      { name: 'Users', route: 'admin.users.index' },
    ],
    client: [{ name: 'Dashboard', route: 'dashboard' }],
  }

  return (
    <>
      <SessionExpiredModal />
      <div className='min-h-screen bg-gray-100'>
        <nav className='bg-white border-b border-gray-100'>
          <div className='max-w-7xl mx-auto px-4 sm:px-6 lg:px-8'>
            <div className='flex justify-between h-16'>
              <div className='flex'>
                <div className='shrink-0 flex items-center'>
                  <Link href='/'>
                    <ApplicationLogo className='block h-9 w-auto text-gray-500' />
                  </Link>
                </div>

                <div className='hidden space-x-8 sm:-my-px sm:ml-10 sm:flex'>
                  {route().current('admin.*') &&
                    routes.admin.map((link) => (
                      <NavLink
                        href={route(link.route)}
                        active={route().current(link.route) as boolean}
                      >
                        {link.name}
                      </NavLink>
                    ))}
                  {!route().current('admin.*') &&
                    routes.client.map((link) => (
                      <NavLink
                        href={route(link.route)}
                        active={route().current(link.route) as boolean}
                      >
                        {link.name}
                      </NavLink>
                    ))}
                </div>
              </div>

              <div className='hidden sm:flex sm:items-center sm:ml-6'>
                <div className='ml-3 relative'>
                  <Menu
                    control={
                      <div>
                        <span className='inline-flex rounded-md'>
                          <button
                            type='button'
                            className='inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 bg-white hover:text-gray-700 focus:outline-none transition ease-in-out duration-150'
                          >
                            {auth.user.name}

                            <ChevronDownIcon className='ml-2 -mr-0.5 h-4 w-' />
                          </button>
                        </span>
                      </div>
                    }
                  >
                    {auth.user.root_admin && route().current('admin.*') ? (
                      <Menu.Item
                        onClick={() => Inertia.visit(route('dashboard'))}
                      >
                        Switch to Client
                      </Menu.Item>
                    ) : (
                      ''
                    )}

                    {auth.user.root_admin && !route().current('admin.*') ? (
                      <Menu.Item
                        onClick={() => Inertia.visit(route('admin.dashboard'))}
                      >
                        Switch to Admin
                      </Menu.Item>
                    ) : (
                      ''
                    )}

                    <Menu.Item onClick={logout} color='red'>
                      Log Out
                    </Menu.Item>
                  </Menu>
                </div>
              </div>

              <div className='-mr-2 flex items-center sm:hidden'>
                <button
                  onClick={() =>
                    setShowingNavigationDropdown(
                      (previousState) => !previousState
                    )
                  }
                  className='inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 focus:text-gray-500 transition duration-150 ease-in-out'
                >
                  <svg
                    className='h-6 w-6'
                    stroke='currentColor'
                    fill='none'
                    viewBox='0 0 24 24'
                  >
                    <path
                      className={
                        !showingNavigationDropdown ? 'inline-flex' : 'hidden'
                      }
                      strokeLinecap='round'
                      strokeLinejoin='round'
                      strokeWidth='2'
                      d='M4 6h16M4 12h16M4 18h16'
                    />
                    <path
                      className={
                        showingNavigationDropdown ? 'inline-flex' : 'hidden'
                      }
                      strokeLinecap='round'
                      strokeLinejoin='round'
                      strokeWidth='2'
                      d='M6 18L18 6M6 6l12 12'
                    />
                  </svg>
                </button>
              </div>
            </div>
          </div>

          <div
            className={
              (showingNavigationDropdown ? 'block' : 'hidden') + ' sm:hidden'
            }
          >
            <div className='pt-2 pb-3 space-y-1'>
              {route().current('admin.*') &&
                routes.admin.map((link) => (
                  <ResponsiveNavLink
                    href={route(link.route)}
                    active={route().current(link.route) as boolean}
                  >
                    {link.name}
                  </ResponsiveNavLink>
                ))}
              {!route().current('admin.*') &&
                routes.client.map((link) => (
                  <ResponsiveNavLink
                    href={route(link.route)}
                    active={route().current(link.route) as boolean}
                  >
                    {link.name}
                  </ResponsiveNavLink>
                ))}
            </div>

            <div className='pt-4 pb-1 border-t border-gray-200'>
              <div className='px-4'>
                <div className='font-medium text-base text-gray-800'>
                  {auth.user.name}
                </div>
                <div className='font-medium text-sm text-gray-500'>
                  {auth.user.email}
                </div>
              </div>

              <div className='mt-3 space-y-1'>
                {auth.user.root_admin && route().current('admin.*') ? (
                  <ResponsiveNavLink href={route('dashboard')}>
                    Switch to Client
                  </ResponsiveNavLink>
                ) : (
                  ''
                )}
                {auth.user.root_admin && !route().current('admin.*') ? (
                  <ResponsiveNavLink href={route('admin.dashboard')}>
                    Switch to Admin
                  </ResponsiveNavLink>
                ) : (
                  ''
                )}
                <ResponsiveNavLink
                  method='post'
                  href={route('logout')}
                  as='button'
                >
                  Log Out
                </ResponsiveNavLink>
              </div>
            </div>
          </div>
        </nav>

        {header && (
          <header className='bg-white shadow'>
            <div className='max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8 truncate'>
              {header}
            </div>
          </header>
        )}

        {secondaryHeader}

        <main>{children}</main>
      </div>
    </>
  )
}
