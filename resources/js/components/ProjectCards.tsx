import { DotsVerticalIcon } from '@heroicons/react/solid'
import classNames from '@/util/classNames'
import { getInitials, stringToHexColor } from '@/util/stringFunctions'
import { Link } from '@inertiajs/inertia-react'

export interface Project {
    name: string
    description?: string
    link: string
}

export interface Props {
    projects: Project[]
}

const ProjectCards = ({ projects }: Props) => {
  return (
    <ul
      role='list'
      className='grid grid-cols-1 gap-5 sm:gap-6 sm:grid-cols-2 lg:grid-cols-4'
    >
      {projects.map((project) => (
        <li key={project.name} className='col-span-1 flex shadow-sm rounded-md'>
          <div
            className='flex-shrink-0 flex items-center justify-center w-16 text-white text-sm font-medium rounded-l-md'
            style={{ backgroundColor: stringToHexColor(project.name) }}
          >
            {getInitials(project.name)}
          </div>
          <div className='flex-1 flex items-center justify-between border-t border-r border-b border-gray-200 bg-white rounded-r-md truncate'>
            <div className='flex-1 px-4 py-2 text-sm truncate'>
              <Link
                href={project.link}
                className='text-gray-900 font-medium hover:text-gray-600'
              >
                {project.name}
              </Link>
              {
                project.description && (
                    <p className='text-gray-500'>{project.description}</p>
                )
              }
            </div>
            <div className='flex-shrink-0 pr-2'>
              <Link
                href={project.link}
                className='w-8 h-8 bg-white inline-flex items-center justify-center text-gray-400 rounded-full bg-transparent hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500'
              >
                <span className='sr-only'>Open options</span>
                <DotsVerticalIcon className='w-5 h-5' aria-hidden='true' />
              </Link>
            </div>
          </div>
        </li>
      ))}
    </ul>
  )
}
