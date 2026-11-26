export const TOKEN_KEY = 'finora_token'

export class ApiError extends Error {
  status: number
  errors: Record<string, string[]>

  constructor(message: string, status: number, errors: Record<string, string[]> = {}) {
    super(message)
    this.name = 'ApiError'
    this.status = status
    this.errors = errors
  }
}

type RequestOptions = {
  method?: 'GET' | 'POST' | 'PUT' | 'PATCH' | 'DELETE'
  body?: unknown
  query?: Record<string, string | number | boolean | undefined>
}

export function getToken(): string | null {
  return localStorage.getItem(TOKEN_KEY)
}

export function setToken(token: string): void {
  localStorage.setItem(TOKEN_KEY, token)
}

export function clearToken(): void {
  localStorage.removeItem(TOKEN_KEY)
}

function buildUrl(path: string, query?: RequestOptions['query']): string {
  const base = (import.meta.env.VITE_API_URL as string | undefined) ?? '/api/v1'
  const url = new URL(`${base}${path}`, window.location.origin)

  if (query) {
    for (const [key, value] of Object.entries(query)) {
      if (value !== undefined && value !== '') {
        url.searchParams.set(key, String(value))
      }
    }
  }

  return url.toString()
}

export async function apiFetch<T>(path: string, options: RequestOptions = {}): Promise<T> {
  const token = getToken()
  const headers: Record<string, string> = {
    Accept: 'application/json',
  }

  if (options.body !== undefined && !(options.body instanceof FormData)) {
    headers['Content-Type'] = 'application/json'
  }
  if (token) {
    headers.Authorization = `Bearer ${token}`
  }

  let response: Response
  try {
    response = await fetch(buildUrl(path, options.query), {
      method: options.method ?? 'GET',
      headers,
      body:
        options.body instanceof FormData
          ? options.body
          : options.body !== undefined
            ? JSON.stringify(options.body)
            : undefined,
    })
  } catch {
    throw new ApiError('Tidak dapat terhubung ke server.', 0)
  }

  if (response.status === 401 && !path.startsWith('/login') && !path.startsWith('/register')) {
    clearToken()
    window.dispatchEvent(new Event('finora:unauthorized'))
  }

  if (!response.ok) {
    let payload: { message?: string; errors?: Record<string, string[]> } = {}
    try {
      payload = (await response.json()) as typeof payload
    } catch {
      // response bukan JSON
    }
    throw new ApiError(
      payload.message ?? `Request gagal (${response.status}).`,
      response.status,
      payload.errors ?? {},
    )
  }

  if (response.status === 204) {
    return undefined as T
  }

  return (await response.json()) as T
}
